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
        try {
            $query = "SELECT {$fields} FROM {$this->getTableName()}";
            if (!empty($this->getTableAlias()))
                $query .= " AS {$this->getTableAlias()}";
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Select builder failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function insertBuilder(string $fields, array $paramns): self
    {
        try {
            $numparams = '';
            foreach ($paramns as $item) {
                $numparams .= ',?';
            }
            $numparams = substr($numparams, 1);
            $fields = implode(',', $this->assertIdentifierList($fields));
            $query = "INSERT INTO {$this->getTableName()} ({$fields}) VALUES ({$numparams})";
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Insert builder failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function updateBuilder(string $fields, array $paramns): self
    {
        try {
            $fields_T = '';
            $atributos = $this->assertIdentifierList($fields);

            foreach ($atributos as $item) {
                $fields_T .= ", {$item} = ?";
            }
            $fields_T = substr($fields_T, 2);
            $query = "UPDATE {$this->getTableName()} SET {$fields_T}";
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Update builder failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $fields
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function deleteBuilder(): self
    {
        try {
            $query = "DELETE FROM {$this->getTableName()}";
            $this->add($query, "main");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Delete builder failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $query
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function query(string $query, array $paramns = []): self
    {
        try {
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Query builder failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $texto
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function where(string $texto, array $paramns = []): self
    {
        try {
            $query = "WHERE {$texto}";
            $this->add($query, "where", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Where clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $condition
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function andWhere(string $condition, array $paramns = []): self
    {
        try {
            $query = "AND {$condition}";
            $this->add($query, "whereConditions", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "And where clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $condition
     * @param array $paramns
     * @return $this
     * @throws DatabaseException
     */
    protected function orWhere(string $condition, array $paramns = []): self
    {
        try {
            $query = "OR {$condition}";
            $this->add($query, "whereConditions", $paramns);
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Or where clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $parameter
     * @param string|null $order
     * @return $this
     * @throws DatabaseException
     */
    protected function orderBy(string $parameter, ?string $order = null): self
    {
        try {
            $query = $this->assertSafeClause("ORDER BY {$parameter} ".($order ?? 'ASC'));
            $this->add($query, "orderBy");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Order by clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $parameter
     * @param string|null $order
     * @return $this
     * @throws DatabaseException
     */
    protected function addOrderBy(string $parameter, ?string $order = null): self
    {
        try {
            $query = $this->assertSafeClause(", {$parameter} ".($order ?? 'ASC')." ");
            $this->add($query, "addOrderBy");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Add order by clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
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
        try {
            $query = $this->assertSafeClause("OFFSET {$texto}");
            $this->add($query,"offset");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Offset clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function groupBy(string $texto): self
    {
        try {
            $query = "GROUP BY {$texto}";
            $this->add($query,"groupBy");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Group by clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function having(string $texto): self
    {
        try {
            $query = "HAVING {$texto}";
            $this->add($query,"having");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Having clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $texto
     * @return $this
     * @throws DatabaseException
     */
    protected function andHaving(string $texto): self
    {
        try {
            $query = "AND {$texto}";
            $this->add($query,"havingConditions");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "And having clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $codition
     * @return $this
     * @throws DatabaseException
     */
    protected function orHaving(string $codition): self
    {
        try {
            $query = "OR {$codition}";
            $this->add($query,"havingConditions");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Or having clause failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
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
        try {
            $query = "INNER JOIN {$table} AS {$alias} ON $codition";
            $this->add($query,"join");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Inner join failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
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
        try {
            $query = "LEFT JOIN {$table} AS {$alias} ON {$codition}";
            $this->add($query,"join");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Left join failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
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
        try {
            $query = "RIGHT JOIN {$table} AS {$alias} ON $codition";
            $this->add($query,"join");
            return $this;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Right join failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return $this
     * @throws DatabaseException
     */
    protected function executeQuery(): self
    {
        try {
            [$query, $params] = $this->buildQuery();
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
        try {
            [$query, $params] = $this->buildQuery();
            echo htmlspecialchars($query, ENT_NOQUOTES) . '<pre>' . htmlspecialchars(print_r($params, true), ENT_NOQUOTES) . '</pre>';
            exit;
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Debug failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
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
        try {
            if (in_array($type, self::SINGLE_PARTS, true)) {
                $this->sqlPartsSelect[$type] = $part;
            } else {
                $this->sqlPartsSelect[$type][] = $part;
            }
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Add query part failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
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
