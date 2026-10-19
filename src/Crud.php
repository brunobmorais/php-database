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
                echo htmlspecialchars($sql, ENT_NOQUOTES);
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
            $fields = implode(',', $this->assertIdentifierList($fields));
            $sql = "INSERT INTO {$this->getTableName()} ({$fields}) VALUES ({$numparams})";
            if ($debug) {
                echo htmlspecialchars($sql, ENT_NOQUOTES) . '<pre>' . htmlspecialchars(print_r($values, true), ENT_NOQUOTES) . '</pre>';
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
                "Insert object failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [" . implode(',', $args) . "]",
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
                "Insert array failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [" . implode(',', (array) $args) . "]",
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
            $atributos = $this->assertIdentifierList($fields);

            foreach ($atributos as $item) {
                $fields_T .= ", {$item} = ?";
            }
            $fields_T = substr($fields_T, 2);
            $sql = "UPDATE {$this->getTableName()} SET {$fields_T}";
            if (isset($where)) {
                $sql .= " WHERE $where";
            }
            if ($debug) {
                echo htmlspecialchars($sql, ENT_NOQUOTES) . '<pre>' . htmlspecialchars(print_r($values, true), ENT_NOQUOTES) . '</pre>';
                return;
            }
            if (!isset($where)) {
                $this->logFullTableWrite('UPDATE');
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
                "Update object failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [" . implode(',', (array) $args) . "]",
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
                "Update array failed - TABLE: [{$this->getTableName()}] MESSAGE: [{$e->getMessage()}] COLUMNS: [" . implode(',', (array) $args) . "]",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Insere várias linhas com INSERT de múltiplas linhas, em lotes (uma ida ao banco por lote).
     * Todas as linhas devem ter as mesmas colunas (chaves); null é gravado como NULL.
     * Sem transação aberta, todos os lotes são gravados numa transação (tudo ou nada).
     * Para models, passe $model->toArray().
     *
     * <code>
     *   $this->insertMany([
     *       ["NOME" => "Ana", "EMAIL" => "a@x.com"],
     *       ["NOME" => "Bia", "EMAIL" => "b@x.com"],
     *   ]);
     * </code>
     *
     * @param array[] $rows
     * @param int $batchSize linhas por INSERT (limitado a 65535 placeholders por comando)
     * @return int linhas inseridas
     * @throws DatabaseException
     */
    public function insertMany(array $rows, int $batchSize = 500): int
    {
        if (empty($rows)) {
            return 0;
        }

        $keys = $this->insertManyColumns($rows);
        $batchSize = max(1, min($batchSize, intdiv(65535, count($keys))));

        return $this->transaction(function () use ($rows, $keys, $batchSize) {
            $total = 0;
            foreach (array_chunk($rows, $batchSize) as $chunk) {
                $total += $this->insertManyBatch($chunk, $keys);
            }
            return $total;
        });
    }

    /**
     * Colunas de insertMany (chaves da primeira linha), validadas e iguais em todas as linhas.
     * @param array $rows
     * @return array
     * @throws DatabaseException
     */
    private function insertManyColumns(array $rows): array
    {
        $first = reset($rows);
        if (!is_array($first) || empty($first)) {
            throw new DatabaseException("insertMany expects non-empty arrays - TABLE: [{$this->getTableName()}]");
        }
        $keys = array_keys($first);
        $this->assertIdentifierList(implode(',', $keys));

        foreach ($rows as $index => $row) {
            if (!is_array($row) || count($row) !== count($keys) || array_diff_key($row, $first)) {
                throw new DatabaseException("insertMany: row [{$index}] has different columns - TABLE: [{$this->getTableName()}]");
            }
        }
        return $keys;
    }

    /**
     * Um INSERT com todas as linhas do lote.
     * @param array $chunk
     * @param array $keys
     * @return int linhas inseridas
     * @throws DatabaseException
     */
    private function insertManyBatch(array $chunk, array $keys): int
    {
        $params = [];
        foreach ($chunk as $row) {
            foreach ($keys as $key) {
                $params[] = $row[$key];
            }
        }

        $columns = implode(',', $this->assertIdentifierList(implode(',', $keys)));
        $rowPlaceholders = '(' . implode(',', array_fill(0, count($keys), '?')) . ')';
        $sql = "INSERT INTO {$this->getTableName()} ({$columns}) VALUES "
            . implode(',', array_fill(0, count($chunk), $rowPlaceholders));

        return $this->executeSQL($sql, $params)->rowCount();
    }

    /**
     * Insere a partir de um model, inclusive propriedades protected/private.
     * Para ModelAbstract equivale a insertArray($model->toArray()). Valores null são ignorados.
     * @param object $model
     * @return bool
     * @throws DatabaseException
     */
    public function insertModel(object $model): bool
    {
        return $this->insertArray($this->modelValues($model));
    }

    /**
     * Atualiza a partir de um model, inclusive propriedades protected/private.
     * Valores null são ignorados, exceto as colunas em $nullColumns, gravadas como NULL.
     * Para um array, passe (object) $array.
     * @param object $model
     * @param string $where
     * @param array $whereValues
     * @param string[] $nullColumns
     * @return bool
     * @throws DatabaseException
     */
    public function updateModel(object $model, string $where, array $whereValues = [], array $nullColumns = []): bool
    {
        $columns = [];
        $values = [];
        foreach ($this->modelValues($model) as $column => $value) {
            if ($value !== null || in_array($column, $nullColumns, true)) {
                $columns[] = $column;
                $values[] = $value;
            }
        }
        // Colunas pedidas como NULL que não existem no model
        foreach ($nullColumns as $column) {
            if (!in_array($column, $columns, true)) {
                $columns[] = $column;
                $values[] = null;
            }
        }

        if (empty($columns)) {
            return false;
        }

        return $this->update(implode(',', $columns), array_merge($values, $whereValues), $where);
    }

    /**
     * Valores do model por coluna: toArray() para ModelAbstract (usa os getters, como toObject());
     * para outros objetos, todas as propriedades de instância (não inicializadas viram null).
     * @param object $model
     * @return array
     */
    private function modelValues(object $model): array
    {
        if ($model instanceof ModelAbstract) {
            return $model->toArray();
        }

        $values = [];
        foreach ((new \ReflectionObject($model))->getProperties() as $prop) {
            if ($prop->isStatic()) {
                continue;
            }
            $prop->setAccessible(true);
            $values[$prop->getName()] = $prop->isInitialized($model) ? $prop->getValue($model) : null;
        }
        return $values;
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
                echo htmlspecialchars($sql, ENT_NOQUOTES) . '<pre>' . htmlspecialchars(print_r($values, true), ENT_NOQUOTES) . '</pre>';
                return;
            }
            if (empty($where)) {
                $this->logFullTableWrite('DELETE');
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
        return $this->getSQL();
    }
}