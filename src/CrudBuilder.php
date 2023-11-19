<?php

namespace BMorais\Database;

/**
 * CLASS ABSTRACT QUERY
 * Basic class to make connection between the database and the application
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */
class CrudBuilder
{
    use DatalayerTrait;


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
     */
    private function select(string $fields = "*", array $paramns = []): CrudBuilder
    {
        try {
            $query = "SELECT {$fields} FROM {$this->getTable()}";
            if (!empty($this->getTableAlias()))
                $query .= " AS {$this->getTableAlias()}";
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $fields
    * @param array $paramns
    * @return $this
     */
    private function insert(string $fields, array $paramns): self
    {
        try {
            $numparams = '';
            foreach ($paramns as $item) {
                $numparams .= ',?';
            }
            $numparams = substr($numparams, 1);
            $query = "INSERT INTO {$this->getTable()} ({$fields}) VALUES ({$numparams})";
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $fields
    * @param array $paramns
    * @return $this
     */
    private function update(string $fields, array $paramns): self
    {
        try {
            $fields_T = '';
            $atributos = explode(',', $fields);

            foreach ($atributos as $item) {
                $fields_T .= ", {$item} = ?";
            }
            $fields_T = substr($fields_T, 2);
            $query = "UPDATE {{$this->getTable()}} SET {$fields_T}";
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $fields
    * @param array $paramns
    * @return $this
     */
    private function delete(): self
    {
        try {
            $query = "DELETE FROM {$this->getTable()}";
            $this->add($query, "main");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $query
    * @param array $paramns
    * @return $this
     */
    private function query(string $query, array $paramns = []): self
    {
        try {
            $this->add($query, "main", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @param array $paramns
    * @return $this
     */
    private function where(string $texto, array $paramns = []): self
    {
        try {
            $query = "WHERE {$texto}";
            $this->add($query, "where", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $condition
    * @param array $paramns
    * @return $this
     */
    private function andWhere(string $condition, array $paramns = []): self
    {
        try {
            $query = "AND {$condition}";
            $this->add($query, "andWhere", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $condition
    * @param array $paramns
    * @return $this
     */
    private function orWhere(string $condition, array $paramns = []): self
    {
        try {
            $query = "OR {$condition}";
            $this->add($query, "orWhere", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $parameter
    * @return $this
     */
    private function orderBy(string $parameter, $order = null): self
    {
        try {
            $query = "ORDER BY {$parameter} ".($order ?? 'ASC');
            $this->add($query, "orderBy");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    private function addOrderBy(string $parameter, $order = null): self
    {
        try {
            $query = ", {$parameter} ".($order ?? 'ASC')." ";
            $this->add($query, "addOrderBy");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    private function limit(string $texto): self
    {
        $query = "LIMIT {$texto}";
        $this->add($query,"limit");
        return $this;
    }

    /**
    * @param string $texto
    * @return $this
     */
    private function offset(string $texto): self
    {
        try {
            $query = "OFFSET {$texto}";
            $this->add($query,"offset");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    private function groupBy(string $texto): self
    {
        try {
            $query = "GROUP BY {$texto}";
            $this->add($query,"groupBy");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    private function having(string $texto): self
    {
        try {
            $query = "HAVING {$texto}";
            $this->add($query,"having");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param string $texto
     * @return $this
     */
    private function andHaving(string $texto): self
    {
        try {
            $query = "AND {$texto}";
            $this->add($query,"andHaving");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @param string $codition
     * @return $this
     */
    private function orHaving(string $codition): self
    {
        try {
            $query = "OR {$codition}";
            $this->add($query,"orHaving");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $table
    * @param string $alias
    * @param string $codition
    * @return $this
     */
    private function innerJoin(string $table, string $alias, string $codition): self
    {
        try {
            $query = "INNER JOIN {$table} AS {$alias} ON $codition";
            $this->add($query,"join");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $table
    * @param string $alias
    * @param string $codition
    * @return $this
     */
    private function leftJoin(string $table, string $alias, string $codition): self
    {
        try {
            $query = "LEFT JOIN {$table} AS {$alias} ON {$codition}";
            $this->add($query,"join");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $table
    * @param string $alias
    * @param string $codition
    * @return $this
     */
    private function rightJoin(string $table, string $alias, string $codition): self
    {
        try {
            $query = "RIGHT JOIN {$table} AS {$alias} ON $codition";
            $this->add($query,"join");
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @return $this
     */
    private function executeQuery(): self
    {
        try {
            foreach ($this->sqlPartsSelect as $key => $part){
                if (is_array($part)) {
                    foreach ($part as $item){
                        $this->query .= $item;
                    }
                } else {
                    $this->query .= $part;
                }

            }

            $this->executeSQL($this->query, $this->params);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @return void
     */
    private function debug()
    {
        try {
            echo $this->query . '<pre>' . print_r($this->params) . '</pre>';
            exit;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @return false|string
     */
    private function lastInsertId(): ?string
    {
        try {
            return $this->lastId();
        } catch (\PDOException $e) {
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
    * @param $params
    * @return self
     */
    private function setParameter($params): self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
    * @param string $query
    * @param string $type
    * @param array $params
    * @return void
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
                $this->params = array_merge($this->params, $params);
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

}
