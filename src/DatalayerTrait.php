<?php
namespace BMorais\Database;
/**
 * CLASSE TRAIT DO DATABASE
 *  Esta classe de métodos de execução no banco
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @version 9
 * @date 2022-06-11
 * @copyright GPL © 2021, bmorais.com
 * @package php
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
     * FUNÇÃOO PARA EXECUTAR SQL
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

    /*$sql = "SELECT * FROM event WHERE eventdate >= :from AND eventdate <= :until AND ( user_name LIKE :st OR site_name LIKE :st )ORDER BY eventdate, start_time LIMIT 100";
    $values = array( 'st'    => '%'.$searchterm.'%','from'  => $fromdate, 'until' => $untildate, );*/
    protected function executaSQLBindValue($sql, $values, $types = false)
    {
        try {
            $this->getInstance($this->database);
            $stmt =  $this->instance->prepare($sql);

            foreach ($values as $key => $value) {
                if ($types) {
                    $stmt->bindValue(":$key", $value, $types[$key]);
                } else {
                    if (is_int($value)) {
                        $param = PDO::PARAM_INT;
                    } elseif (is_bool($value)) {
                        $param = PDO::PARAM_BOOL;
                    } elseif (is_null($value)) {
                        $param = PDO::PARAM_NULL;
                    } elseif (is_string($value)) {
                        $param = PDO::PARAM_STR;
                    } else {
                        $param = FALSE;
                    }

                    if ($param) $stmt->bindValue(":$key", $value, $param);
                }
            }

            $stmt->execute();

        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }

        return $stmt;

    }

    /**
     * FUN��O PARA RETORNAR A QUANTIDADE DE ELEMENTOS DE UM OBJETO SQL
     */
    protected function count($prepare): int
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
     * FUN��O CONSULTA FEITA E RETORNA UM ARRAY DE OBJETOS
     */
    protected function getObjAssoc($prepare){
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_ASSOC);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    /**
     * FUN��O CONSULTA FEITA E RETORNA UM ARRAY DE OBJETOS
     */
    protected function getObj($prepare){
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $dados = $prepare->fetchAll(PDO::FETCH_OBJ);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    protected function getObjModel($prepare, String $class){
        try {
            $prepare = empty($prepare)?$this->prepare:$prepare;
            $dados = $prepare->fetchObject(CONFIG_DATA_LAYER["directory_models"].$class);
            return $dados;
        } catch (PDOException $e) {
            Connect::setError($e);
            return false;
        }
    }

    protected function startTransaction()
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

    protected function cancelTransaction(){

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

    //METODOS ORIENTADO A OBJETO
    /*Método select que retorna um VO ou um array de objetos*/
    protected function selectDB($sql, $params = null, $class = null)
    {
        try {
            $this->getInstance($this->database);
            $this->prepare = $this->instance->prepare($sql);
            $this->prepare->execute($params);

            if (!empty($class)) {
                $rs = $this->getObjModel($this->prepare,$class);
            } else {
                $rs = $this->getObj($this->prepare);
            }
        } catch (PDOException $e) {
            Connect::setError($e,$sql);
            return false;
        }
        return $rs;
    }

    /*Método insert que insere valores no banco de dados e retorna o último id inserido*/
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

    /*Método update que altera valores do banco de dados e retorna o número de linhas afetadas*/
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

    /*Método delete que excluí valores do banco de dados retorna o número de linhas afetadas*/
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

    protected function printErrorInfo()
    {
        return $this->getInstance()->errorInfo();
    }
}

