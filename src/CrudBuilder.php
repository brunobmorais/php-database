<?php

namespace BMorais\Database;

/**
 * CLASS CrudBuilder
 * Basic class to make connection between the database and the application
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access protected
 */
abstract class CrudBuilder extends Crud
{
    /** Partes que aceitam uma única ocorrência (a última chamada substitui a anterior) */
    private const SINGLE_PARTS = ['where', 'having', 'orderBy', 'limit', 'offset'];

    /**
     * Ordem de montagem do SQL. Cada parte guarda ['sql' => string, 'params' => array],
     * assim os parâmetros são ligados na mesma ordem em que aparecem no SQL.
     * AND/OR de where e having ficam numa única lista para preservar a ordem das chamadas.
     */
    private const EMPTY_PARTS = [
        'main'             => [],
        'join'             => [],
        'where'            => null,
        'whereConditions'  => [],
        'groupBy'          => [],
        'having'           => null,
        'havingConditions' => [],
        'orderBy'          => null,
        'addOrderBy'       => [],
        'limit'            => null,
        'offset'           => null,
    ];

    /**
     * @var array
     */
    private array $sqlPartsSelect = self::EMPTY_PARTS;

    /** Parâmetros da última consulta executada por executeQuery() */
    private array $executedParams = [];

    /**
     *
     * <code>
     *   $qb = $this->select('u.id, p.id')
     *           ->where('phonenumbers=?', [$number]);
     * </code>
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function selectBuilder(string $fields = "*", array $paramns = []): self
    {
        $query = "SELECT {$fields} FROM {$this->getTableName()}";
        if (!empty($this->getTableAlias()))
            $query .= " AS {$this->getTableAlias()}";
        $this->add($query, "main", $paramns);
        return $this;
    }

    /**
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function insertBuilder(string $fields, array $paramns): self
    {
        $numparams = '';
        foreach ($paramns as $item) {
            $numparams .= ',?';
        }
        $numparams = substr($numparams, 1);
        $fields = implode(',', $this->assertIdentifierList($fields));
        $query = "INSERT INTO {$this->getTableName()} ({$fields}) VALUES ({$numparams})";
        $this->add($query, "main", $paramns);
        return $this;
    }

    /**
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function updateBuilder(string $fields, array $paramns): self
    {
        $fields_T = '';
        $atributos = $this->assertIdentifierList($fields);

        foreach ($atributos as $item) {
            $fields_T .= ", {$item} = ?";
        }
        $fields_T = substr($fields_T, 2);
        $query = "UPDATE {$this->getTableName()} SET {$fields_T}";
        $this->add($query, "main", $paramns);
        return $this;
    }

    /**
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function deleteBuilder(): self
    {
        $query = "DELETE FROM {$this->getTableName()}";
        $this->add($query, "main");
        return $this;
    }

    /**
     * @param string $query
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function query(string $query, array $paramns = []): self
    {
        $this->add($query, "main", $paramns);
        return $this;
    }

    /**
     * @param string $texto
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function where(string $texto, array $paramns = []): self
    {
        $query = "WHERE {$texto}";
        $this->add($query, "where", $paramns);
        return $this;
    }

    /**
     * @param string $condition
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function andWhere(string $condition, array $paramns = []): self
    {
        $query = "AND {$condition}";
        $this->add($query, "whereConditions", $paramns);
        return $this;
    }

    /**
     * @param string $condition
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function orWhere(string $condition, array $paramns = []): self
    {
        $query = "OR {$condition}";
        $this->add($query, "whereConditions", $paramns);
        return $this;
    }

    /**
     * @param string $parameter
     * @param string|null $order
     * @return $this
     * @throws DatabaseException
     */
    protected function orderBy(string $parameter, ?string $order = null): self
    {
        $query = $this->assertSafeClause("ORDER BY {$parameter} ".($order ?? 'ASC'));
        $this->add($query, "orderBy");
        return $this;
    }

    /**
     * @param string $parameter
     * @param string|null $order
     * @return $this
     * @throws DatabaseException
     */
    protected function addOrderBy(string $parameter, ?string $order = null): self
    {
        $query = $this->assertSafeClause(", {$parameter} ".($order ?? 'ASC')." ");
        $this->add($query, "addOrderBy");
        return $this;
    }

    /**
     * @param string $texto "10" ou "offset, quantidade" (ex.: "20, 10")
     * @return $this
     * @throws DatabaseException
     */
    protected function limit(string $texto): self
    {
        $query = $this->assertSafeClause("LIMIT {$texto}");
        $this->add($query,"limit");
        return $this;
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function offset(string $texto): self
    {
        $query = $this->assertSafeClause("OFFSET {$texto}");
        $this->add($query,"offset");
        return $this;
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function groupBy(string $texto): self
    {
        $query = "GROUP BY {$texto}";
        $this->add($query,"groupBy");
        return $this;
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function having(string $texto): self
    {
        $query = "HAVING {$texto}";
        $this->add($query,"having");
        return $this;
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function andHaving(string $texto): self
    {
        $query = "AND {$texto}";
        $this->add($query,"havingConditions");
        return $this;
    }

    /**
     * @param string $codition
     * @return $this
     * @throws DatabaseException
     */
    protected function orHaving(string $codition): self
    {
        $query = "OR {$codition}";
        $this->add($query,"havingConditions");
        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     * @param string $codition
     * @return $this
     * @throws DatabaseException
     */
    protected function innerJoin(string $table, string $alias, string $codition): self
    {
        $query = "INNER JOIN {$table} AS {$alias} ON $codition";
        $this->add($query,"join");
        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     * @param string $codition
     * @return $this
     * @throws DatabaseException
     */
    protected function leftJoin(string $table, string $alias, string $codition): self
    {
        $query = "LEFT JOIN {$table} AS {$alias} ON {$codition}";
        $this->add($query,"join");
        return $this;
    }

    /**
     * @param string $table
     * @param string $alias
     * @param string $codition
     * @return $this
     * @throws DatabaseException
     */
    protected function rightJoin(string $table, string $alias, string $codition): self
    {
        $query = "RIGHT JOIN {$table} AS {$alias} ON $codition";
        $this->add($query,"join");
        return $this;
    }

    /**
     * @return $this
     * @throws DatabaseException
     */
    protected function executeQuery(): self
    {
        try {
            [$query, $params] = $this->buildQuery();
            // updateBuilder()/deleteBuilder() sem where: só registra em log, não bloqueia
            if (preg_match('/^\s*(UPDATE|DELETE)\b/i', $query, $m) === 1 && preg_match('/\bWHERE\b/i', $query) !== 1) {
                $this->logFullTableWrite(strtoupper($m[1]));
            }
            // Parâmetros passados manualmente via setParams() continuam valendo, antes dos do builder
            $params = array_merge(parent::getParams(), $params);
            // Limpa antes de executar para que uma falha não contamine a próxima consulta
            $this->resetQueryParts();
            $this->clearParams();
            $this->executedParams = $params;
            $this->setQuery($query);

            $this->executeSQL($query, $params);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Execute query failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e,
                $this->getQuery(),
                $this->getParams()
            );
        }
    }

    /**
     * @return void
     * @throws DatabaseException
     */
    protected function debug(): void
    {
        [$query, $params] = $this->buildQuery();
        echo htmlspecialchars($query, ENT_NOQUOTES) . '<pre>' . htmlspecialchars(print_r($params, true), ENT_NOQUOTES) . '</pre>';
        exit;
    }

    /**
     * @param string $query
     * @param string $type
     * @param array $params
     * @return void
     * @throws DatabaseException
     */
    private function add(string $query, string $type, array $params = []): void
    {
        $part = ['sql' => $query . " ", 'params' => $params];
        if (in_array($type, self::SINGLE_PARTS, true)) {
            $this->sqlPartsSelect[$type] = $part;
        } else {
            $this->sqlPartsSelect[$type][] = $part;
        }
    }

    /**
     * Parâmetros pendentes (setParams) ou, se não houver, os da última consulta executada.
     * @return array
     */
    protected function getParams(): array
    {
        return parent::getParams() ?: $this->executedParams;
    }

    /**
     * Descarta a consulta em construção.
     * @return $this
     */
    protected function resetQueryParts(): self
    {
        $this->sqlPartsSelect = self::EMPTY_PARTS;
        return $this;
    }

    /**
     * Monta o SQL e os parâmetros na ordem de EMPTY_PARTS.
     * @return array{0: string, 1: array}
     */
    private function buildQuery(): array
    {
        $sql = '';
        $params = [];
        foreach ($this->sqlPartsSelect as $type => $part) {
            $items = in_array($type, self::SINGLE_PARTS, true) ? array_filter([$part]) : $part;
            foreach ($items as $item) {
                $sql .= $item['sql'];
                $params = array_merge($params, $item['params']);
            }
        }
        return [$sql, $params];
    }

    /**
     * Usado em ORDER BY, LIMIT e OFFSET. Aceita expressões (RAND(), FIELD(...), "10 OFFSET 20"...)
     * para não quebrar o uso existente, mas bloqueia terminador de comando e comentários,
     * usados para encadear ou truncar SQL injetado.
     * Ainda assim, não repasse valores da requisição direto: use whitelist de colunas e (int) em limit/offset.
     * @param string $clause
     * @return string
     * @throws DatabaseException
     */
    private function assertSafeClause(string $clause): string
    {
        if (preg_match('~;|--|#|/\*|\*/~', $clause) === 1) {
            throw new DatabaseException("Invalid SQL clause - TABLE: [{$this->getTableName()}] VALUE: [{$clause}]");
        }
        return $clause;
    }
}
