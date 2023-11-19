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
        'main'     => [],
        'join'     => [],
        'where'    => "",
        'andWhere' => [],
        'orWhere'  => [],
        'groupBy'  => [],
        'having'   => [],
        'andHaving'=> [],
        'orHaving' => [],
        'orderBy'  => "",
        'limit'    => "",
        'offset'   => "",

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
    public function select(string $fields = "*", array $paramns = []): CrudBuilder
    {
        try {
            $query = "SELECT {$fields} FROM {$this->getTable()}";
            if (!empty($this->getTableAlias()))
                $query .= "AS {$this->getTableAlias()} ";
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
    public function insert(string $fields, array $paramns): self
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
    public function delete(string $fields, array $paramns): self
    {
        try {
            $query = "DELETE FROM {$this->getTable()}";
            $this->add($query, "main", $paramns);
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
    public function query(string $query, array $paramns = []): self
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
    public function where(string $texto, array $paramns = []): self
    {
        try {
            $query = "WHERE {$texto} ";
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
    public function andWhere(string $condition, array $paramns = []): self
    {
        try {
            $query = "AND {$condition} ";
            $this->add($query, "andWhere", $paramns);
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
            $this->add($query, "orWhere", $paramns);
            return $this;
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function orderBy(string $texto, $order = null): self
    {
        try {
            $query = "ORDER BY {$texto} ".($order ?? 'ASC')." ";
            $this->add($query, "orderBy");
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
        $query = "LIMIT {$texto}";
        $this->add($query,"limit");
        return $this;
    }

    /**
    * @param string $texto
    * @return $this
     */
    public function offset(string $texto): self
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
    public function groupBy(string $texto): self
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
    public function having(string $texto): self
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
    public function andHaving(string $texto): self
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
    public function orHaving(string $codition): self
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
    public function innerJoin(string $table, string $alias, string $codition): self
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
    public function leftJoin(string $table, string $alias, string $codition): self
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
    public function rightJoin(string $table, string $alias, string $codition): self
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
    public function executeQuery(): self
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
    protected function getSQL(): ?string
    {
        try {
            return $this->logSQL ?? "";
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

    public function setParameter($params): self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    private function add(string $text, string $type, array $params = [])
    {
        $text = $text." ";
        try {
            if (is_array($this->sqlPartsSelect[$type])) {
                $this->sqlPartsSelect[$type][] = $text;
            } else {
                $this->sqlPartsSelect[$type] = $text;
            }

            if (!empty($params))
                $this->params = array_merge($this->params, $params);
        } catch (\PDOException $e) {
            $this->setError($e);
        }
    }

}
