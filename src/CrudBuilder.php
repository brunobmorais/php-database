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

    public function select(string $fields = "*", array $paramns = []):self
    {
        $query = "SELECT {$fields} FROM {$this->getTable()} ";
        if (!empty($this->getTableAlias()))
            $query .= "AS {$this->getTableAlias()}";
        $this->add($query, $paramns);
        return $this;
    }

    public function insert(string $fields, array $paramns):self
    {
        $numparams = '';
        foreach ($paramns as $item) {
            $numparams .= ',?';
        }
        $numparams = substr($numparams, 1);
        $query = "INSERT INTO {$this->getTable()} ({$fields}) VALUES ({$numparams})";
        $this->add($query, $paramns);
        return $this;
    }

    public function update(string $fields, array $paramns):self
    {
        $fields_T = '';
        $atributos = explode(',', $fields);

        foreach ($atributos as $item) {
            $fields_T .= ", {$item} = ?";
        }
        $fields_T = substr($fields_T, 2);
        $query = "UPDATE {{$this->getTable()}} SET {$fields_T}";
        $this->add($query, $paramns);
        return $this;
    }

    public function delete(string $fields, array $paramns):self
    {
        $query = "DELETE FROM {$this->getTable()}";
        $this->add($query, $paramns);
        return $this;
    }

    public function query(string $texto, array $paramns): self
    {
        $this->add($texto, $paramns);
        return $this;
    }

    public function where(string $texto, array $paramns): self
    {
        $query = "WHERE {$texto} ";
        $this->add($query, $paramns);
        return $this;
    }

    public function andWhere(string $condition, array $paramns): self
    {
        $query = "AND {$condition} ";
        $this->add($query, $paramns);
        return $this;
    }

    public function orWhere(string $texto, array $paramns): self
    {
        $query = "OR {$texto} ";
        $this->add($query, $paramns);
        return $this;
    }

    public function orderBy(string $texto): self
    {
        $query = "ORDER BY {$texto} ";
        $this->add($query);
        return $this;
    }

    public function limit(string $texto): self
    {
        $query = "LIMIT {$texto} ";
        $this->add($query);
        return $this;
    }

    public function offset(string $texto): self
    {
        $query = "OFFSET {$texto} ";
        $this->add($query);
        return $this;
    }

    public function groupBy(string  $texto): self{
        $query = "GROUP BY {$texto} ";
        $this->add($query);
        return $this;
    }

    public function having(string $texto): self
    {
        $query = "HAVING {$texto} ";
        $this->add($query);
        return $this;
    }

    public function innerJoin(string $table, string $alias, string $codition): self
    {
        $query = "INNER JOIN {$table} AS {$alias} ON $codition ";
        $this->add($query);
        return $this;
    }

    public function leftJoin(string $table, string $alias, string $codition): self
    {
        $query = "LEFT JOIN {$table} AS {$alias} ON $codition ";
        $this->add($query);
        return $this;
    }

    public function rightJoin(string $table, string $alias, string $codition): self
    {
        $query = "RIGHT JOIN {$table} AS {$alias} ON $codition ";
        $this->add($query);
        return $this;
    }

    public function execute(): self
    {
        $this->executeSQL($this->query, $this->params);
        return $this;
    }

    public function debug()
    {
        return  $this->query.'<pre>'.print_r($this->params).'</pre>';
    }

    /**
     * @return false|string
     */
    public function lastInsertId(): ?string
    {
        return $this->lastId();
    }

    /**
     * @return string|null
     */
    public function getLogSQL(): ?string
    {
        return $this->logSQL ?? "";
    }

    private function add(string $text, array $params = [])
    {
        if (!empty($params))
            $this->params[] = $params;
        $this->query .= $text;
    }

}
