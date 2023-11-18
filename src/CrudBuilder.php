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

    /**
    * @param string $fields
    * @param array $paramns
    * @return $this
     */
    public function select(string $fields = "*", array $paramns = []): CrudBuilder
    {
        try {
            $query = "SELECT {$fields} FROM {$this->getTable()} ";
            if (!empty($this->getTableAlias()))
                $query .= "AS {$this->getTableAlias()} ";
            $this->add($query, $paramns);
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
    public function insert(string $fields, array $paramns): self
    {
        try {
            $numparams = '';
            foreach ($paramns as $item) {
                $numparams .= ',?';
            }
            $numparams = substr($numparams, 1);
            $query = "INSERT INTO {$this->getTable()} ({$fields}) VALUES ({$numparams})";
            $this->add($query, $paramns);
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
    public function update(string $fields, array $paramns): self
    {
        try {
            $fields_T = '';
            $atributos = explode(',', $fields);

            foreach ($atributos as $item) {
                $fields_T .= ", {$item} = ?";
            }
            $fields_T = substr($fields_T, 2);
            $query = "UPDATE {{$this->getTable()}} SET {$fields_T}";
            $this->add($query, $paramns);
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
    public function delete(string $fields, array $paramns): self
    {
        try {
            $query = "DELETE FROM {$this->getTable()}";
            $this->add($query, $paramns);
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
    public function query(string $texto, array $paramns = []): self
    {
        try {
            $this->add($texto, $paramns);
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
    public function where(string $texto, array $paramns = []): self
    {
        try {
            $query = "WHERE {$texto} ";
            $this->add($query, $paramns);
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
    public function andWhere(string $condition, array $paramns = []): self
    {
        try {
            $query = "AND {$condition} ";
            $this->add($query, $paramns);
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
    public function orWhere(string $texto, array $paramns = []): self
    {
        try {
            $query = "OR {$texto} ";
            $this->add($query, $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function orderBy(string $texto): self
    {
        try {
            $query = "ORDER BY {$texto} ";
            $this->add($query);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function limit(string $texto): self
    {
        $query = "LIMIT {$texto} ";
        $this->add($query);
        return $this;
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function offset(string $texto): self
    {
        try {
            $query = "OFFSET {$texto} ";
            $this->add($query);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function groupBy(string $texto): self
    {
        try {
            $query = "GROUP BY {$texto} ";
            $this->add($query);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function having(string $texto): self
    {
        try {
            $query = "HAVING {$texto} ";
            $this->add($query);
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
    public function innerJoin(string $table, string $alias, string $codition): self
    {
        try {
            $query = "INNER JOIN {$table} AS {$alias} ON $codition ";
            $this->add($query);
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
    public function leftJoin(string $table, string $alias, string $codition): self
    {
        try {
            $query = "LEFT JOIN {$table} AS {$alias} ON $codition ";
            $this->add($query);
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
    public function rightJoin(string $table, string $alias, string $codition): self
    {
        try {
            $query = "RIGHT JOIN {$table} AS {$alias} ON $codition ";
            $this->add($query);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
     * @return $this
     */
    public function execute(): self
    {
        try {
            $this->executeSQL($this->query, $this->params);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @return void
     */
    public function debug()
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
    public function lastInsertId(): ?string
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
    public function getLogSQL(): ?string
    {
        try {
            return $this->logSQL ?? "";
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    private function add(string $text, array $params = [])
    {
        try {
            if (!empty($params))
                $this->params = array_merge($this->params, $params);
            $this->query .= $text;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

}
