<?php
namespace BMorais\Database;

use PDO;
use PDOException;
use PDOStatement;
use stdClass;

/**
 * CLASS TRAIT DATALAYER
 * This class of execution methods in the database
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */
trait DatalayerTrait
{
    /** @var PDO|null conexão em uso pelo DAO (prefira setInstance()/getInstance()) */
    protected ?PDO $instance = null;

    /** @var array<string, PDO|null> conexões em cache por DSN */
    private static array $instances = [];

    /** @var string */
    private string $currentDsn = '';

    /** @var int incrementado por closeConnections() para invalidar conexões ainda seguradas pelos DAOs */
    private static int $connectionsGeneration = 0;

    /** @var int geração da conexão em $instance */
    private int $instanceGeneration = 0;

    /** @var string|null banco já verificado quanto ao sufixo de homologação */
    private ?string $homologationCheckedFor = null;

    /** @var string */
    protected string $fields;

    /** @var PDOStatement|null último statement executado (prefira getPrepare()) */
    protected $prepare = null;

    /** @var string banco do DAO (prefira setDatabase(), que aplica o sufixo de homologação) */
    protected string $database = CONFIG_DATA_LAYER["dbname"];

    /** @var string model usado nos fetch*Class (prefira setClassModel()) */
    protected string $classModel;

    /** @var string tabela do DAO (prefira setTableName(), que também aceita o alias) */
    protected string $tableName;

    /** @var string */
    private string $tableAlias;

    /** @var array
     */
    private array $data = [];

    /** @var string|null SQL interpolado, montado sob demanda por getSQL() */
    private ?string $logSQL = null;

    /** @var string */
    private string $logQuery = '';

    /** @var array|null */
    private ?array $logParams = null;

    /** @var PDOException */
    private $error;

    /** @var string */
    private string $query = "";

    /** @var array */
    private array $params = [];


    /**
     * @return PDO
     * @throws DatabaseException
     */
    private function getConnect(): PDO
    {
        try {
            $this->applyHomologationSuffix();

            // Após closeConnections(), solta a conexão antiga que este DAO ainda segura para que ela
            // seja de fato fechada — exceto instância injetada e transação em andamento
            if ($this->instance !== null && $this->currentDsn !== ''
                && $this->instanceGeneration !== self::$connectionsGeneration
                && !$this->instance->inTransaction()) {
                $this->instance = null;
            }

            // Se uma instância foi injetada externamente via setInstance(), respeitá-la
            if ($this->instance !== null) {
                return $this->instance;
            }

            $dsn = $this->buildDsn();
            $this->currentDsn = $dsn;

            $pdo = self::$instances[$dsn] ?? null;
            if (!$pdo instanceof PDO) {
                $pdo = new PDO(
                    $dsn,
                    CONFIG_DATA_LAYER['username'],
                    CONFIG_DATA_LAYER['passwd'],
                    CONFIG_DATA_LAYER['options']
                );
                self::$instances[$dsn] = $pdo;
            }

            $this->instance = $pdo;
            $this->instanceGeneration = self::$connectionsGeneration;
            return $pdo;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Database connection failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$this->connectionErrorSummary($e)}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Indica se o ambiente é de homologação.
     * Prioridade: CONFIG_DATA_LAYER['homologation_active'] → env DB_HOMOLOGATION → SERVER_NAME (legado).
     * @return bool
     */
    private function isHomologation(): bool
    {
        if (array_key_exists('homologation_active', CONFIG_DATA_LAYER)) {
            return (bool) CONFIG_DATA_LAYER['homologation_active'];
        }

        $env = getenv('DB_HOMOLOGATION');
        if ($env !== false && $env !== '') {
            return filter_var($env, FILTER_VALIDATE_BOOLEAN);
        }

        // Fallback legado: SERVER_NAME pode vir do header Host (Apache com UseCanonicalName Off),
        // ou seja, é controlável pelo cliente. Defina 'homologation_active' para não depender dele.
        $homologation = CONFIG_DATA_LAYER['homologation'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        return $homologation !== '' && $serverName !== '' && str_contains($serverName, mb_strtolower($homologation));
    }

    /**
     * @param string $database
     * @return string
     */
    private function withHomologationSuffix(string $database): string
    {
        $suffix = ucfirst(CONFIG_DATA_LAYER['homologation'] ?? '');
        if ($suffix === '' || str_contains($database, $suffix) || !$this->isHomologation()) {
            return $database;
        }
        return $database . $suffix;
    }

    /**
     * Resumo da falha de conexão sem usuário, host ou banco (a mensagem do PDO costuma trazê-los
     * e acaba exibida ao usuário). A mensagem original continua em getPrevious().
     * @param PDOException $e
     * @return string
     */
    private function connectionErrorSummary(PDOException $e): string
    {
        $message = $e->getMessage();
        $prefix = preg_match('/^SQLSTATE\[[^\]]*\](?:\s*\[\d+\])?/', $message, $m) === 1 ? $m[0] : 'CODE ' . $e->getCode();

        $reasons = [
            1044 => 'acesso negado ao banco',
            1045 => 'acesso negado',
            1049 => 'banco inexistente',
            2002 => 'servidor inacessível',
            2005 => 'servidor inacessível',
            2006 => 'conexão perdida',
            2013 => 'conexão perdida',
        ];
        $driverCode = preg_match('/\[(\d+)\]/', $prefix, $c) === 1 ? (int) $c[1] : (int) $e->getCode();
        $reason = $reasons[$driverCode] ?? 'falha ao conectar';

        return "{$prefix} {$reason}";
    }

    /**
     * Aplica o sufixo de homologação ao banco, verificando só quando o nome do banco muda.
     * Atribui direto (sem setDatabase) para não descartar uma instância injetada via setInstance().
     * @return void
     */
    private function applyHomologationSuffix(): void
    {
        if ($this->homologationCheckedFor !== $this->getDatabase()) {
            $this->database = $this->withHomologationSuffix($this->getDatabase());
            $this->homologationCheckedFor = $this->database;
        }
    }

    /**
     * @return string
     */
    private function buildDsn(): string
    {
        return CONFIG_DATA_LAYER['driver'] . ':host=' . CONFIG_DATA_LAYER['host'] . ';dbname=' . $this->getDatabase() . ';port=' . CONFIG_DATA_LAYER['port'];
    }

    public static function closeConnections(): void {
        // Conexões com transação aberta continuam no cache, para que DAOs criados depois
        // participem da mesma transação em vez de abrir outra conexão
        self::$instances = array_filter(
            self::$instances,
            fn($pdo) => $pdo instanceof PDO && $pdo->inTransaction()
        );
        self::$connectionsGeneration++;
    }


    /**
     * @param ?PDO $pdo
     * @return Crud
     *
     */
    protected function setInstance(?PDO $pdo): self
    {
        $this->instance = $pdo;
        // Instância externa: não pertence ao cache de conexões, então não deve ser reconectada
        $this->currentDsn = '';
        return $this;
    }

    /**
     * @return PDO
     * @throws DatabaseException
     */
    protected function getInstance(): PDO
    {
        return $this->getConnect();
    }

    /**
     * @param string $database
     * @return $this
     */
    protected function setDatabase(string $database): self
    {
        $this->database = $this->withHomologationSuffix($database);
        // Nulifica a instância para que getConnect() recalcule o DSN com o novo banco
        $this->instance = null;

        return $this;
    }

    /**
     * @return string
     */
    protected function getDatabase(): string
    {
        return $this->database ?? "";
    }

    /**
     * @param string $fields
     * @return Crud
     */
    protected function setFields(string $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @return string
     */
    protected function getFields():string
    {
        return $this->fields ?? "";
    }

    /**
     * @param string $tableName
     * @param string $tableAlias
     * @return CrudBuilder|Crud|DatalayerTrait
     */
    protected function setTableName(string $tableName, string $tableAlias = ""): self
    {
        if (!empty($tableAlias))
            $this->tableAlias = $tableAlias;
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return string
     */
    protected function getTableName(): string
    {
        return $this->tableName ?? "";
    }

    protected function getTableAlias(): string
    {
        return $this->tableAlias ?? "";
    }

    /**
     * @param string $classModel
     * @return Crud
     */
    protected function setClassModel(string $classModel): self
    {
        $this->classModel = $classModel;
        return $this;
    }

    protected function getClassModel(): string
    {
        return $this->classModel ?? "";
    }

    /**
     * @param string $classModel
     * @return Crud
     */
    protected function setPrepare(PDOStatement $prepare): self
    {
        $this->prepare = $prepare;
        return $this;
    }

    protected function getPrepare(): PDOStatement
    {
        if ($this->prepare === null) {
            throw new DatabaseException("No statement executed - TABLE: [{$this->getTableName()}]");
        }
        return $this->prepare;
    }

    /**
     * @param $params
     * @return self
     */
    protected function setParams($params): self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * @return self
     */
    protected function clearParams(): self
    {
        $this->params = [];
        return $this;
    }

    /**
     * @param $params
     * @return array
     */
    protected function getParams(): array
    {
        return $this->params;
    }

    protected function getData(): array
    {
        return $this->data;
    }

    protected function setData(array $array): self
    {
        $this->data = $array;
        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function setQuery(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @param string $query
     * @param array|null $params
     * @param bool $retry
     * @return PDOStatement
     * @throws DatabaseException
     */
    protected function executeSQL(string $query, ?array $params = null, bool $retry = true): PDOStatement
    {
        try {
            // Libera o resultado e o statement anteriores antes da nova consulta, reduzindo o pico de memória
            $this->data = [];
            $this->prepare = null;
            $this->setPrepare($this->getInstance()->prepare($query));
            if (CONFIG_DATA_LAYER['display_errors_details']) {
                $this->setSQL($query, $params);
            }
            $this->getPrepare()->execute($params);
            return $this->getPrepare();
        } catch (PDOException $e) {
            // MySQL server has gone away (2006) ou Lost connection (2013)
            if (in_array($e->errorInfo[1] ?? 0, [2006, 2013], true) && !empty($this->currentDsn)) {
                $canRetry = $retry && $this->isSafeToRetry($e, $query);
                // Descarta a conexão morta mesmo sem retry, para não ficar presa no cache
                self::$instances[$this->currentDsn] = null;
                $this->instance = null;
                if ($canRetry) {
                    return $this->executeSQL($query, $params, false);
                }
            }
            throw new DatabaseException(
                "SQL execution failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e,
                $query,
                $params
            );
        }
    }

    /**
     * Só reconecta e repete quando não há risco de duplicar escrita ou perder transação:
     * - nunca dentro de transação (a nova conexão estaria em autocommit e o commit viraria no-op);
     * - 2006: o comando não chegou a ser executado;
     * - 2013: a conexão caiu durante a query, que pode já ter rodado — só repete leituras.
     * @param PDOException $e
     * @param string $query
     * @return bool
     */
    private function isSafeToRetry(PDOException $e, string $query): bool
    {
        try {
            if ($this->instance !== null && $this->instance->inTransaction()) {
                return false;
            }
        } catch (PDOException) {
            return false;
        }

        $code = $e->errorInfo[1] ?? 0;
        if ($code === 2006) {
            return true;
        }

        return $code === 2013 && preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $query) === 1;
    }

    /**
     * Valida um identificador SQL (coluna ou tabela.coluna), opcionalmente entre `crases`, "aspas" ou [colchetes].
     * @param string $identifier
     * @return string identificador sem espaços nas pontas
     * @throws DatabaseException
     */
    protected function assertIdentifier(string $identifier): string
    {
        $part = '(?:[\p{L}_][\p{L}\p{N}_$]*|`[^`]+`|"[^"]+"|\[[^\]]+\])';
        $identifier = trim($identifier);
        if (preg_match('/^' . $part . '(?:\.' . $part . ')?$/u', $identifier) !== 1) {
            throw new DatabaseException("Invalid SQL identifier - TABLE: [{$this->getTableName()}] IDENTIFIER: [{$identifier}]");
        }
        return $identifier;
    }

    /**
     * Valida uma lista de colunas separadas por vírgula.
     * @param string $fields
     * @return string[]
     * @throws DatabaseException
     */
    protected function assertIdentifierList(string $fields): array
    {
        return array_map(fn(string $field) => $this->assertIdentifier($field), explode(',', $fields));
    }

    /**
     * @param string $classModel
     * @return void
     * @throws DatabaseException
     */
    private function assertClassModel(string $classModel): void
    {
        if ($classModel === '') {
            throw new DatabaseException("Class model not set, use setClassModel() - TABLE: [{$this->getTableName()}]");
        }
    }

    /**
     * Registra no log de erros do PHP um UPDATE/DELETE sem WHERE (afeta a tabela inteira).
     * Usa error_log() em vez de trigger_error() para nunca interromper a execução,
     * mesmo com handlers que convertem warnings em exceção.
     * @param string $operation
     * @return void
     */
    protected function logFullTableWrite(string $operation): void
    {
        error_log("bmorais/database: {$operation} sem WHERE na tabela [{$this->getTableName()}] afeta todas as linhas");
    }

    /**
     * @param PDOStatement|null $prepare
     * @return int|null
     * @throws DatabaseException
     */
    protected function rowCount(?PDOStatement $prepare = null): ?int
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            return $prepare->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Row count failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param PDOStatement|null $prepare
     * @return array|null
     * @throws DatabaseException
     */
    protected function fetchArrayAssoc(?PDOStatement $prepare = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);
            $this->setData($dados);
            return $dados;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fetch array assoc failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param PDOStatement|null $prepare
     * @return array|null
     * @throws DatabaseException
     */
    protected function fetchArrayObj(?PDOStatement $prepare = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_OBJ);
            $this->setData($dados);
            return $dados;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fetch array object failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param PDOStatement|null $prepare
     * @param String|null $classModel
     * @return array|null
     * @throws DatabaseException
     */
    protected function fetchArrayClass(?PDOStatement $prepare = null, ?string $classModel = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $classModel = empty($classModel) ? $this->getClassModel() : $classModel;
            $this->assertClassModel($classModel);
            $dados = $prepare->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, CONFIG_DATA_LAYER["directory_models"] . $classModel);
            $this->setData($dados);
            return $dados;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fetch array class failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param PDOStatement|null $prepare
     * @return array|null
     * @throws DatabaseException
     */
    protected function fetchOneAssoc(?PDOStatement $prepare = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $data = $prepare->fetch(PDO::FETCH_ASSOC);
            return $data === false ? null : $data;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fetch one assoc failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param PDOStatement|null $prepare
     * @return stdClass|null
     * @throws DatabaseException
     */
    protected function fetchOneObj(?PDOStatement $prepare = null): ?stdClass
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $data = $prepare->fetch(PDO::FETCH_OBJ);
            return $data instanceof stdClass ? $data : null;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fetch one object failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param PDOStatement|null $prepare
     * @param String|null $class
     * @return object|null
     * @throws DatabaseException
     */
    protected function fetchOneClass(?PDOStatement $prepare = null, ?string $class = null): ?object
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $class = empty($class) ? $this->getClassModel() : $class;
            $this->assertClassModel($class);
            $data = $prepare->fetchObject(CONFIG_DATA_LAYER["directory_models"] . $class);
            return $data === false ? null : $data;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fetch one class failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return bool|null
     * @throws DatabaseException
     */
    protected function beginTransaction(): ?bool
    {
        try {
            // Verifica se já existe uma transação aberta
            if ($this->getInstance()->inTransaction()) {
                return true;
            }

            $this->getInstance()->beginTransaction();
            return true;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Begin transaction failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return bool|null
     * @throws DatabaseException
     */
    protected function commitTransaction(): ?bool
    {
        try {
            // Verifica se já existe uma transação aberta
            if ($this->getInstance()->inTransaction()) {
                $this->getInstance()->commit();
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Commit transaction failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return bool|null
     * @throws DatabaseException
     */
    protected function rollBackTransaction(): ?bool
    {
        try {

            // Verifica se já existe uma transação aberta
            if ($this->getInstance()->inTransaction()) {
                $this->getInstance()->rollBack();
            }

            return true;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Rollback transaction failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Percorre o resultado linha a linha (generator), sem carregar tudo em memória.
     * Para exportações e relatórios grandes.
     *
     * Usa uma conexão própria, fechada ao final; no MySQL ela é unbuffered, então o resultado
     * não fica inteiro em memória no cliente. Por ser outra conexão, não enxerga dados ainda
     * não commitados da transação em andamento. A consulta só é executada na primeira iteração.
     *
     * <code>
     *   foreach ($this->cursor("SELECT * FROM PEDIDO WHERE DATA >= ?", [$inicio]) as $pedido) {
     *       fputcsv($arquivo, (array) $pedido);
     *   }
     * </code>
     *
     * @param string $query
     * @param array|null $params
     * @param bool $returnModel true: instâncias de classModel; false: stdClass
     * @return \Generator
     * @throws DatabaseException
     */
    protected function cursor(string $query, ?array $params = null, bool $returnModel = false): \Generator
    {
        $this->applyHomologationSuffix();

        $options = (CONFIG_DATA_LAYER['options'] ?? []);
        $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $options[PDO::ATTR_PERSISTENT] = false;
        if (CONFIG_DATA_LAYER['driver'] === 'mysql') {
            $unbuffered = defined('Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY')
                ? constant('Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY')
                : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;
            $options[$unbuffered] = false;
        }

        try {
            $pdo = new PDO($this->buildDsn(), CONFIG_DATA_LAYER['username'], CONFIG_DATA_LAYER['passwd'], $options);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Database connection failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$this->connectionErrorSummary($e)}]",
                $e->getCode(),
                $e
            );
        }

        try {
            $statement = $pdo->prepare($query);
            $statement->execute($params);
            if ($returnModel) {
                $this->assertClassModel($this->getClassModel());
                // setFetchMode(FETCH_CLASS, string $class) é a forma documentada; o stub do analisador é antigo
                $statement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, /** @scrutinizer ignore-type */ CONFIG_DATA_LAYER["directory_models"] . $this->getClassModel());
            } else {
                $statement->setFetchMode(PDO::FETCH_OBJ);
            }

            while (($row = $statement->fetch()) !== false) {
                yield $row;
            }
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Cursor failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e,
                $query,
                $params
            );
        } finally {
            // Fechar a conexão (e não só o cursor) evita ler o restante do resultado
            // quando o laço é interrompido antes do fim
            $statement = null;
            $pdo = null;
        }
    }

    /**
     * Placeholders para IN (...), para não concatenar valores no SQL.
     * Lista vazia vira "NULL" (IN (NULL) não retorna linhas, enquanto IN () é erro de sintaxe).
     *
     * <code>
     *   $sql = "SELECT * FROM PESSOA WHERE CODPESSOA IN (" . $this->placeholders($ids) . ") AND ATIVO = ?";
     *   $this->executeSQL($sql, [...array_values($ids), 1]);
     * </code>
     *
     * @param array $values
     * @return string
     */
    protected function placeholders(array $values): string
    {
        return empty($values) ? 'NULL' : implode(',', array_fill(0, count($values), '?'));
    }

    /**
     * Executa $callback dentro de uma transação: commit ao final e rollback em qualquer erro
     * (a exceção é relançada). Com $attempts > 1, repete o bloco inteiro em deadlock (1213)
     * ou lock wait timeout (1205). Se já houver transação aberta, apenas participa dela:
     * quem a abriu decide o commit/rollback.
     *
     * <code>
     *   $id = $this->transaction(function () use ($dados) {
     *       $this->insertArray($dados);
     *       return $this->lastInsertId();
     *   }, 3);
     * </code>
     *
     * @param callable $callback recebe o próprio DAO
     * @param int $attempts tentativas em caso de deadlock/lock wait timeout
     * @return mixed retorno do $callback
     * @throws \Throwable
     */
    protected function transaction(callable $callback, int $attempts = 1): mixed
    {
        if ($this->getInstance()->inTransaction()) {
            return $callback($this);
        }

        for ($attempt = 1; ; $attempt++) {
            $this->beginTransaction();
            try {
                $result = $callback($this);
                $this->commitTransaction();
                return $result;
            } catch (\Throwable $e) {
                try {
                    $this->rollBackTransaction();
                } catch (\Throwable) {
                    // conexão perdida: o servidor já descartou a transação; vale o erro original
                }
                if ($attempt < $attempts && $this->isDeadlock($e)) {
                    usleep(random_int(20, 100) * 1000 * $attempt);
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Deadlock (1213 / SQLSTATE 40001) ou lock wait timeout (1205), na exceção ou na anterior.
     * @param \Throwable $e
     * @return bool
     */
    private function isDeadlock(\Throwable $e): bool
    {
        for ($ex = $e; $ex !== null; $ex = $ex->getPrevious()) {
            if ($ex instanceof PDOException
                && (in_array((int) ($ex->errorInfo[1] ?? 0), [1213, 1205], true) || ($ex->errorInfo[0] ?? '') === '40001')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string|null
     * @throws DatabaseException
     */
    private function lastId(): ?string
    {
        try {
            return $this->getInstance()->lastInsertId();
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Get last insert ID failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Guarda a query e os parâmetros; a interpolação só é feita quando getSQL()/getLogSQL() é chamado.
     * @param $sql_string
     * @param array|null $params
     * @return void
     */
    private function setSQL($sql_string, ?array $params = null)
    {
        // Valores muito grandes (arquivos/blobs) não ficam retidos em memória só para o log
        foreach ($params ?? [] as $key => $value) {
            if (is_string($value) && strlen($value) > 65536) {
                $params[$key] = '[' . strlen($value) . ' bytes]';
            }
        }

        $this->logQuery = (string) $sql_string;
        $this->logParams = $params;
        $this->logSQL = null;
    }

    /**
     * Monta o SQL com os valores no lugar dos placeholders. Só para log/debug — nunca executar.
     * Substitui em uma única passada, então valores contendo "?", ":nome" ou "$1" não afetam o resultado.
     * @param string $sql
     * @param array|null $params
     * @return string
     */
    private function interpolateSQL(string $sql, ?array $params): string
    {
        if (empty($params)) {
            return $sql;
        }

        // Mesma formatação de antes; null = valor não representável, mantém o placeholder
        $format = function ($v): ?string {
            if (is_object($v)) {
                return $v instanceof \DateTime ? $v->format('Y-m-d H:i:s') : null;
            }
            if (is_string($v)) {
                return "'$v'";
            }
            if ($v === null) {
                return 'NULL';
            }
            if (is_array($v)) {
                return implode(',', $v);
            }
            return (string) $v;
        };

        if (array_keys($params) === range(0, count($params) - 1)) {
            $i = 0;
            $result = preg_replace_callback('/\?/', function ($m) use (&$i, $params, $format) {
                if (!array_key_exists($i, $params)) {
                    return $m[0];
                }
                return $format($params[$i++]) ?? $m[0];
            }, $sql);
        } else {
            $named = [];
            foreach ($params as $k => $v) {
                $named[ltrim((string) $k, ':')] = $v;
            }
            // (?<!:) evita casts do PostgreSQL como "valor::int"
            $result = preg_replace_callback('/(?<!:):(\w+)/', function ($m) use ($named, $format) {
                return array_key_exists($m[1], $named) ? ($format($named[$m[1]]) ?? $m[0]) : $m[0];
            }, $sql);
        }

        return $result ?? $sql;
    }

    /**
     * @return string|null
     */
    protected function getSQL(): ?string
    {
        if ($this->logSQL === null) {
            $this->logSQL = $this->interpolateSQL($this->logQuery, $this->logParams);
        }
        return $this->logSQL;
    }

    /**
     * @param PDOException $e
     * @return void
     * @throws DatabaseException
     * @deprecated This method is no longer used - exceptions are thrown directly
     */
    protected function setError(PDOException $e)
    {
        $this->error = $e;
        throw new DatabaseException(
            "Database error - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
            $e->getCode(),
            $e
        );
    }
}