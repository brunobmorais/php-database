<?php

namespace BMorais\Database;

use PDOException;

/**
 * CLASS ABSTRACT CRUD
 * Basic class to make connection between the database and the application
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */

abstract class Crud
{
    use DatalayerTrait;

    /**
     * @param string $fields
     * @param string $add
     * @param array|null $values
     * @param bool $returnModel
     * @param bool $debug
     * @return array|false|void|null
     * @throws DatabaseException
     */
    public function select(string $fields = '*', string $add = '', ?array $values = null, bool $returnModel = false, bool $debug = false)
    {
        try {
            if (strlen($add) > 0) {
                $add = ' ' . $add;
            }

            $sql = "SELECT {$fields} FROM {$this->getTableName()}";

            if (!empty($this->getTableAlias()))
                $sql .= " AS {$this->getTableAlias()}";

            $sql .= $add;

            if ($debug) {
                echo $sql;
                return;
            }
            $this->executeSQL($sql, $values);
            if ($returnModel) {
                return $this->fetchArrayClass();
            } else {
                return $this->fetchArrayObj();
            }
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Query failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [{$fields}]",
                $e->getCode(),
                $e,
                $sql ?? '',
                $values
            );
        }
    }

    /**
     * @param string $fields
     * @param array|null $values
     * @param bool $debug
     * @return bool|void
     * @throws DatabaseException
     */
    public function insert(string $fields, ?array $values = null, bool $debug = false)
    {
        try {
            $numparams = '';
            foreach ($values as $item) {
                $numparams .= ',?';
            }
            $numparams = substr($numparams, 1);
            $sql = "INSERT INTO {$this->getTableName()} ({$fields}) VALUES ({$numparams})";
            if ($debug) {
                echo $sql . '<pre>' . print_r($values) . '</pre>';
                return;
            }
            $result = $this->executeSQL($sql, $values);
            return !empty($result);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Insert failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [{$fields}]",
                $e->getCode(),
                $e,
                $sql ?? '',
                $values
            );
        }
    }

    /**
     * @param object $object
     * @return bool|null
     * @throws DatabaseException
     */
    public function insertObject(object $object)
    {
        try {
            $args = [];
            $params = [];

            // Filtrar propriedades do objeto que não são null
            foreach ($object as $chave => $valor) {
                if ($valor !== null) {  // Verifica explicitamente se o valor não é null
                    $args[] = $chave;
                    $params[] = $valor;
                }
            }

            // Se houver colunas a serem inseridas
            if (!empty($args)) {
                $columns = implode(',', $args);
                return $this->insert($columns, $params);
            }

            // Retorna falso se todos os valores forem null
            return false;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Insert object failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [{$columns}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array $object
     * @return bool
     * @throws DatabaseException
     */
    public function insertArray(array $object)
    {
        try {
            $args = [];
            $params = [];
            foreach ($object as $chave => $valor) {
                if ($valor !== null) {
                    $args[] = $chave;
                    $params[] = $valor;
                }
            }

            if (!empty($args)) {
                $args = implode(',', $args);
                return $this->insert($args, $params);
            }

            return false;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Insert array failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [{$args}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param string $fields
     * @param array|null $values
     * @param string|null $where
     * @param bool $debug
     * @return bool|void
     * @throws DatabaseException
     */
    public function update(string $fields, ?array $values = null, ?string $where = null, bool $debug = false)
    {
        try {
            $fields_T = '';
            $atributos = explode(',', $fields);

            foreach ($atributos as $item) {
                $fields_T .= ", {$item} = ?";
            }
            $fields_T = substr($fields_T, 2);
            $sql = "UPDATE {$this->getTableName()} SET {$fields_T}";
            if (isset($where)) {
                $sql .= " WHERE $where";
            }
            if ($debug) {
                echo $sql . '<pre>' . print_r($values) . '</pre>';
                return;
            }
            $result = $this->executeSQL($sql, $values);
            return !empty($result);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Update failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: {$fields}",
                $e->getCode(),
                $e,
                $sql ?? '',
                $values
            );
        }
    }

    /**
     * @param object $object
     * @param string $where
     * @param array $whereValues
     * @return bool|null
     * @throws DatabaseException
     */
    public function updateObject(object $object, string $where, array $whereValues = [])
    {
        try {
            $args = [];
            $params = [];
            foreach ($object as $chave => $valor) {
                if ($valor !== null) {
                    $args[] = $chave;
                    $params[] = $valor;
                }
            }

            // Adiciona os valores do WHERE no final
            $params = array_merge($params, $whereValues);

            if (!empty($args)) {
                $args = implode(',', $args);
                return $this->update($args, $params, $where);
            }

            return false;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Update object failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [{$args}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array $object
     * @param string $where
     * @param array $whereValues
     * * @return bool
     * @throws DatabaseException
     */
    public function updateArray(array $object, string $where, array $whereValues = [])
    {
        try {
            $args = [];
            $params = [];

            foreach ($object as $chave => $valor) {
                if ($valor !== null) {
                    $args[] = $chave;
                    $params[] = $valor;
                }
            }

            // Adiciona os valores do WHERE no final
            $params = array_merge($params, $whereValues);

            if (!empty($args)) {
                $args = implode(',', $args);
                return $this->update($args, $params, $where);
            }

            return false;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Update array failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [{$args}]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array|null $values
     * @param string|null $where
     * @param bool $debug
     * @return bool|void
     * @throws DatabaseException
     */
    public function delete(?array $values = null, ?string $where = null, bool $debug = false)
    {
        try {
            $sql = "DELETE FROM {$this->getTableName()}";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            if ($debug) {
                echo $sql . '<pre>' . print_r($values) . '</pre>';
                return;
            }
            $result = $this->executeSQL($sql, $values);
            return !empty($result);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Delete failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}]",
                $e->getCode(),
                $e,
                $sql ?? '',
                $values
            );
        }
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
}