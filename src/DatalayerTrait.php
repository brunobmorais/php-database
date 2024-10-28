<?php
namespace BMorais\Database;

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
use PDO;
use PDOException;
use PDOStatement;
use stdClass;

trait DatalayerTrait
{
    /** @var PDO|null
     * @deprecated USE $this->setClassModel("NAME");
     * */
    protected $instance = null;

    /** @var string
     *  @deprecated
     */
    protected string $fields;

    /** @var PDOStatement|null
     *   @deprecated */
    protected $prepare = null;

    /** @var string
     *  @deprecated
     */
    protected string $database = CONFIG_DATA_LAYER["dbname"];

    /** @var string
     *  @deprecated USE $this->setInstance(PDO);
     */
    protected string $classModel;

    /** @var string
     * @deprecated USE $this->setTableName("name");
     */
    protected string $tableName;

    /** @var string */
    private string $tableAlias;

    /** @var array
     */
    private array $data = [];

    /** @var string */
    private string $logSQL;

    /** @var PDOException */
    private $error;

    /** @var string */
    private string $query = "";

    /** @var array */
    private array $params = [];


    /** @return PDO|false */
    private function getConnect()
    {
        try {
            if (strpos($_SERVER['SERVER_NAME'], mb_strtolower(CONFIG_DATA_LAYER["homologation"])) && !strpos($this->getDatabase(), ucfirst(CONFIG_DATA_LAYER["homologation"]))) {
                $database = $this->getDatabase().ucfirst(CONFIG_DATA_LAYER["homologation"] ?? "");
                $this->setDatabase($database);
            }

            if (empty($this->instance)) {
                $this->instance = new PDO(
                    CONFIG_DATA_LAYER['driver'] . ':host=' . CONFIG_DATA_LAYER['host'] . ';dbname=' . $this->getDatabase() . ';port=' . CONFIG_DATA_LAYER['port'],
                    CONFIG_DATA_LAYER['username'],
                    CONFIG_DATA_LAYER['passwd'],
                    CONFIG_DATA_LAYER['options']
                );
            }

            return $this->instance;
        } catch (PDOException $e) {
            $this->setError($e);
        }

    }

    /**
     * @param ?PDO $pdo
     * @return Crud
     *
     */
    protected function setInstance(?PDO $pdo): self
    {
        $this->instance = $pdo;
        return $this;
    }

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
        if (strpos($_SERVER['SERVER_NAME'], mb_strtolower(CONFIG_DATA_LAYER["homologation"])) && !strpos($database, ucfirst(CONFIG_DATA_LAYER["homologation"]))) {
            $database = $database.ucfirst(CONFIG_DATA_LAYER["homologation"] ?? "");
            $this->database = $database;
        } else {
            $this->database = $database;
        }

        if (!empty($this->instance)){
            $this->executeSQL("USE {$this->getDatabase()}");
        }

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
        return $this->fields;
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
        return $this->classModel;
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
    public  function getQuery(): string
    {
        return $this->query;
    }
    public  function setQuery(string $query): self
    {
        $this->query = $query;
        return $this;
    }

    /**
        * @param string $query
        * @param array|null $params
        * @return PDOStatement|void
     */
    protected function executeSQL(string $query, ?array $params = null)
    {
        try {
            $this->setPrepare($this->getInstance()->prepare($query));
            $this->setSQL($query, $params);
            $this->getPrepare()->execute($params);
            return $this->getPrepare();
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param $prepare
     * @return int|false
     */
    protected function rowCount(PDOStatement $prepare = null): ?int
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            return $prepare->rowCount();
        } catch (PDOException $e) {
            $this->setError($e);}
    }

    /**
    * @param PDOStatement|null $prepare
    * @return array|null
     */
    protected function fetchArrayAssoc(PDOStatement $prepare = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);
            $this->setData($dados);
            return $dados;
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param $prepare
     * @return array|false
     */
    protected function fetchArrayObj(PDOStatement $prepare = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_OBJ);
            $this->setData($dados);
            return $dados;
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param $prepare
     * @param String|null $classModel
     * @return array|false
     */
    protected function fetchArrayClass(PDOStatement $prepare = null, string $classModel = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $classModel = empty($classModel) ? $this->getClassModel() : $classModel;
            $dados = $prepare->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, CONFIG_DATA_LAYER["directory_models"] . $classModel);
            $this->setData($dados);
            return $dados;
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param $prepare
     * @return array|false
     */
    protected function fetchOneAssoc(PDOStatement $prepare = null): ?array
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            return $prepare->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param PDOStatement|null $prepare
    * @return stdClass|null
     */
    protected function fetchOneObj(PDOStatement $prepare = null): ?stdClass
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            return $prepare->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param $prepare
     * @param String|null $class
     * @return array|false
     */
    protected function fetchOneClass(PDOStatement $prepare = null, string $class = null): ?object
    {
        try {
            $prepare = empty($prepare) ? $this->getPrepare() : $prepare;
            $class = empty($class) ? $this->getClassModel() : $class;
            return $prepare->fetchObject(CONFIG_DATA_LAYER["directory_models"] . $class);
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @return bool
     */
    protected function beginTrasaction(): ?bool
    {
        try {
            $this->getInstance()->beginTransaction();
            return true;
        } catch (PDOException $e) {
            $this->setError($e);
        }

    }

    /**
     * @return bool|null
     */
    protected function commitTransaction(): ?bool
    {
        try {
            $this->getInstance()->commit();
            return true;
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @return bool|null
     *
     */
    protected function rollBackTransaction(): ?bool
    {

        try {
            $this->getInstance()->rollBack();
            return true;
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     *  @return string|null
     *  */
    private function lastId(): ?string
    {
        try {
            return $this->getInstance()->lastInsertId();
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param $sql_string
     * @param array|null $params
     * @return void
     */
    private function setSQL($sql_string, ?array $params = null)
    {
        try {
            if (!empty($params)) {
                $indexed = $params == array_values($params);
                foreach ($params as $k => $v) {
                    if (is_object($v)) {
                        if ($v instanceof \DateTime) {
                            $v = $v->format('Y-m-d H:i:s');
                        } else {
                            continue;
                        }
                    } elseif (is_string($v)) {
                        $v = "'$v'";
                    } elseif ($v === null) {
                        $v = 'NULL';
                    } elseif (is_array($v)) {
                        $v = implode(',', $v);
                    }

                    if ($indexed) {
                        $sql_string = preg_replace('/\?/', $v, $sql_string, 1);
                    } else {
                        if ($k[0] != ':') {
                            $k = ':' . $k;
                        } //add leading colon if it was left out
                        $sql_string = str_replace($k, $v, $sql_string);
                    }
                }
            }
            $this->logSQL = $sql_string;
        } catch (PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @return string|null
     */
    protected function getSQL(): ?string
    {
        try {
            return $this->logSQL ?? "";
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param PDOException $e
     * @return void
     */
    protected function setError(PDOException $e)
    {
        $this->error = $e;
        throw new PDOException("{$e->getMessage()} ###### SQL: {$this->getSQL()}");

    }
}
