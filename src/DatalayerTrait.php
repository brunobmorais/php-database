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


trait DatalayerTrait
{
    /** @var PDO */
    protected $instance;
    protected $params;
    protected $prepare = null;
    protected $database=CONFIG_DATA_LAYER["dbname"];
    protected $classModel;
    protected $tableName;
    protected $resultArray = array();

    /**
     * @param $database
     * @return PDO|null
     */
    private function getInstance($database){
        if (strpos($_SERVER['SERVER_NAME'],"homologacao") && !strpos($database,"homologacao") )
            $database .= "Homologacao";

        if (!isset($this->instance)) {
            $this->instance = Connect::getInstance($database);
            return $this->instance;
        } else {
            return $this->instance;
        }
    }

    /**
     * @param String $query
     * @param array|null $params
     * @return false|\PDOStatement|null
     */
    protected function executeSQL(String $query, ?array $params = null){
        try {
            $this->getInstance($this->database);
            $this->prepare =  $this->instance->prepare($query);
            $this->prepare->execute($params);
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
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $qtd = $prepare->rowCount();
            return $qtd;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }


    }

    /**
     * @param $prepare
     * @return false
     */
    protected function fetchArrayAssoc($prepare=null): array{
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
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
     * @return false
     */
    protected function fetchArrayObj($prepare=null): array{
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
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
     * @param String $class
     * @return false
     */
    protected function fetchArrayClass($prepare=null, String $class=null): array{
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $class = empty($class)?$this->classModel:$class;
            $dados = $prepare->fetchAll(PDO::FETCH_CLASS, CONFIG_DATA_LAYER["directory_models"].$class);
            $this->resultArray = $dados;
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @return false
     */
    protected function fetchOneAssoc($prepare=null){
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $dados = $prepare->fetch(PDO::FETCH_ASSOC);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @return false
     */
    protected function fetchOneObj($prepare=null){
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $dados = $prepare->fetch(PDO::FETCH_OBJ);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @param $prepare
     * @return false
     */
    protected function fetchOneClass($prepare=null, String $class=null){
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $class = empty($class)?$this->classModel:$class;
            $dados = $prepare->fetchObject(CONFIG_DATA_LAYER["directory_models"].$class);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function beginTrasaction()
    {
        try {
            $this->getInstance($this->database);
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
    protected function commitTransaction(){
        try {
            $this->getInstance($this->database);
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
    protected function rollBackTransaction(){

        try {
            $this->getInstance($this->database);
            $this->instance->rollBack();
            return true;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * RETORNAR O ULTIMO ID INSERIDO
     */
    private function lastId()
    {
        $this->getInstance($this->database);
        $ultimo = $this->instance->lastInsertId();
        return $ultimo;

    }

    /**
     * @param $sql
     * @param $params
     * @param $class
     * @return false
     */
    protected function selectDB($sql, $params = null, $class = null)
    {
        try {
            $this->getInstance($this->database);
            $this->prepare = $this->instance->prepare($sql);
            $this->prepare->execute($params);

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
    protected function insertDB($sql, $params = null)
    {
        try {
            $this->getInstance($this->database);
            $this->prepare = $this->instance->prepare($sql);
            $rs = $this->prepare->execute($params);
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
    protected function updateDB($sql, $params = null)
    {
        try {
            $this->getInstance($this->database);
            $query = $this->instance->prepare($sql);
            $rs = $query->execute($params);
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
    protected function deleteDB($sql, $params = null)
    {
        try {
            $this->getInstance($this->database);;
            $this->prepare = $this->instance->prepare($sql);
            $rs = $this->prepare->execute($params);
        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }
        return $rs;
    }

    /**
     * @return array
     */
    protected function printErrorInfo()
    {
        return $this->getInstance($this->database)->errorInfo();
    }
}
