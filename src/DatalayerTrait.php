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
            $this->setLogSQL($query, $params);
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

    /**
     * @param $sql_string
     * @param array|null $params
     * @return void
     */
    function setLogSQL($sql_string, array $params = null) {
        if (!empty($params)) {
            $indexed = $params == array_values($params);
            foreach($params as $k=>$v) {
                if (is_object($v)) {
                    if ($v instanceof \DateTime) $v = $v->format('Y-m-d H:i:s');
                    else continue;
                }
                elseif (is_string($v)) $v="'$v'";
                elseif ($v === null) $v='NULL';
                elseif (is_array($v)) $v = implode(',', $v);

                if ($indexed) {
                    $sql_string = preg_replace('/\?/', $v, $sql_string, 1);
                }
                else {
                    if ($k[0] != ':') $k = ':'.$k; //add leading colon if it was left out
                    $sql_string = str_replace($k,$v,$sql_string);
                }
            }
        }
        $this->logSQL = $sql_string;
    }
}
