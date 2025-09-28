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
    /**
     * @var array
     */
    private array $sqlPartsSelect = [
        'main'      => [],
        'join'      => [],
        'where'     => "",
        'andWhere'  => [],
        'orWhere'   => [],
        'groupBy'   => [],
        'having'    => [],
        'andHaving' => [],
        'orHaving'  => [],
        'orderBy'   => "",
        'addOrderBy'=> [],
        'limit'     => "",
        'offset'    => "",
    ];

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
            $atributos = explode(',', $fields);

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
            $this->add($query, "andWhere", $paramns);
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
            $this->add($query, "orWhere", $paramns);
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
            $query = "ORDER BY {$parameter} ".($order ?? 'ASC');
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
            $query = ", {$parameter} ".($order ?? 'ASC')." ";
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
     * @param string $texto
     * @return $this
     */
    protected function limit(string $texto): self
    {
        $query = "LIMIT {$texto}";
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
            $query = "OFFSET {$texto}";
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
            $this->add($query,"andHaving");
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
            $this->add($query,"orHaving");
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
            foreach ($this->sqlPartsSelect as $key => $part){
                if (is_array($part)) {
                    foreach ($part as $item){
                        $this->setQuery($this->getQuery().$item);
                    }
                } else {
                    $this->setQuery($this->getQuery().$part);
                }

            }

            $this->executeSQL($this->getQuery(), $this->getParams());
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
            echo $this->getQuery() . '<pre>' . print_r($this->getParams()) . '</pre>';
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
        $query = $query." ";
        try {
            if (is_array($this->sqlPartsSelect[$type])) {
                $this->sqlPartsSelect[$type][] = $query;
            } else {
                $this->sqlPartsSelect[$type] = $query;
            }

            if (!empty($params))
                $this->setParams($params);
        } catch (\PDOException $e) {
            throw new DatabaseException(
                "Add query part failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e
            );
        }
    }
}