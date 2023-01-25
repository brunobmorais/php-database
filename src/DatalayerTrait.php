<?php
namespace BMorais\Database;
/**
 * CLASSE TRAIT DO DATABASE
 *  Esta classe de métodos de execução no banco
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright GPL © 2022, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */

use PDO;
use PDOException;
use stdClass;


trait DatalayerTrait
{
    /** @var PDO */
    protected $instance;
    protected $params;
    protected $prepare = null;
    protected $database = CONFIG_DATA_LAYER["dbname"];
    protected $classModel;
    protected $tableName;
    protected $resultArray = array();

    private $logSQL;

    /**
     * @return PDO|null
     */
    private function getInstance(): ?PDO
    {
        if (strpos($_SERVER['SERVER_NAME'], "homologacao") && !strpos($this->database, "Homologacao") )
            $this->database .= "Homologacao";

        if (!isset($this->instance)) {
            $this->instance = Connect::getInstance($this->database);
            return $this->instance;
        } else {
            return $this->instance;
        }
    }

    /**
     * @param String $query
     * @param array|null $params
     * @return false|mixed|\PDOStatement|null
     */
    protected function executeSQL(String $query, ?array $params = null)
    {
        try {
            $this->getInstance();
            $this->prepare = $this->instance->prepare($query);
            $this->prepare->execute($params);
            $this->logSQL = $this->prepare->queryString;
        } catch (PDOException $e) {
            Connect::setError($e,$query);
            return false;
        }

        return $this->prepare;
    }

    /**
     * @param $prepare
     * @return int
     */
    protected function count($prepare=null): int
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $qtd = $prepare->rowCount();
            return $qtd;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }


    }

    /**
     * @param $prepare
     * @return array|false
     */
    protected function fetchArrayAssoc($prepare=null): array
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);
            $this->resultArray = $dados;
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @return array|false
     */
    protected function fetchArrayObj($prepare=null): array
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_OBJ);
            $this->resultArray = $dados;
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @param String|null $class
     * @return array|false
     */
    protected function fetchArrayClass($prepare=null, String $class=null): array
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $class = empty($class) ? $this->classModel : $class;
            $dados = $prepare->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, CONFIG_DATA_LAYER["directory_models"] . $class);
            $this->resultArray = $dados;
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @return array|false
     */
    protected function fetchOneAssoc($prepare=null): array
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $dados = $prepare->fetch(PDO::FETCH_ASSOC);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @return stdClass|false
     */
    protected function fetchOneObj($prepare=null): stdClass
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $dados = $prepare->fetch(PDO::FETCH_OBJ);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @param String|null $class
     * @return array|false
     */
    protected function fetchOneClass($prepare=null, String $class=null): object
    {
        try {
            $prepare = empty($prepare) ? $this->prepare : $prepare;
            $class = empty($class) ? $this->classModel : $class;
            $dados = $prepare->fetchObject(CONFIG_DATA_LAYER["directory_models"] . $class);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function beginTrasaction(): bool
    {
        try {
            $this->getInstance();
            $this->instance->beginTransaction();
            return true;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }

    }

    /**
     * @return bool
     */
    protected function commitTransaction(): bool
    {
        try {
            $this->getInstance();
            $this->instance->commit();
            return true;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function rollBackTransaction(): bool
    {

        try {
            $this->getInstance();
            $this->instance->rollBack();
            return true;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $sql
     * @param $params
     * @param $class
     * @return array|false
     */
    protected function selectDB($sql, $params = null, $class = null)
    {
        try {
            $this->getInstance();
            $this->prepare = $this->instance->prepare($sql);
            $this->prepare->execute($params);
            $this->logSQL = $this->prepare->queryString;

            if (!empty($class)) {
                $rs = $this->fetchArrayClass($this->prepare,$class);
            } else {
                $rs = $this->fetchArrayObj($this->prepare);
            }
        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }
        return $rs;
    }

    /**
     * @param $sql
     * @param $params
     * @return bool
     */
    protected function insertDB($sql, $params = null): bool
    {
        try {
            $this->getInstance();
            $this->prepare = $this->instance->prepare($sql);
            $rs = $this->prepare->execute($params);
            $this->logSQL = $this->prepare->queryString;
        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }
        return $rs;
    }

    /**
     * @param $sql
     * @param $params
     * @return bool
     */
    protected function updateDB($sql, $params = null): bool
    {
        try {
            $this->getInstance();
            $query = $this->instance->prepare($sql);
            $rs = $query->execute($params);
            $this->logSQL = $this->prepare->queryString;
        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }
        return $rs;
    }

    /**
     * @param $sql
     * @param $params
     * @return bool
     */
    protected function deleteDB($sql, $params = null): bool
    {
        try {
            $this->getInstance();;
            $this->prepare = $this->instance->prepare($sql);
            $rs = $this->prepare->execute($params);
            $this->logSQL = $this->prepare->queryString;
        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }
        return $rs;
    }

    /**
    * RETORNAR O ULTIMO ID INSERIDO
    */
    private function lastId()
    {
        $this->getInstance();
        $ultimo = $this->instance->lastInsertId();
        return $ultimo;

    }

    /**
     * @return array
     */
    protected function printErrorInfo(): array
    {
        return $this->getInstance($this->database)->errorInfo();
    }
}
