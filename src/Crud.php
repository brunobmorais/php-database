<?php
namespace BMorais\Database;


/**
 * CLASSE CRUD
 * Classe abastrada para fazer ligação entre o banco e aplicação
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright GPL © 2022, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */
abstract class Crud {

    use DatalayerTrait;

    /**
     * @param string $fields
     * @param string $add
     * @param array|null $values
     * @param bool $fetchobj
     * @param bool $debug
     * @return array|false|void|\stdClass
     */
    public function select(string $fields = "*", string $add = "", array $values = null, bool $returnModel = false, bool $debug = false){
        if(strlen($add)>0)
            $add = " ".$add;
        $sql = "SELECT {$fields} FROM {$this->tableName}{$add}";
        if ($debug){echo $sql; exit;}

        if ($returnModel)
            return $this->selectDB($sql,$values,$this->classModel);
        else
            return $this->selectDB($sql,$values,null);
    }

    /**
     * @param String $fields
     * @param array|null $values
     * @param $debug
     * @return bool|void
     */
    public function insert(string $fields, array $values = null, $debug = false){
        $numparams="";
        for($i=0; $i<count($values); $i++)
            $numparams.=",?";
        $numparams = substr($numparams,1);
        $sql = "INSERT INTO ".$this->tableName." ($fields) VALUES ($numparams)";
        if ($debug){echo $sql; var_dump($values);exit;}
        $t=$this->insertDB($sql,$values);
        return $t;
    }

    /**
     * @param string $fields
     * @param array|null $values
     * @param string|null $where
     * @param bool $debug
     * @return bool|void
     */
    public function update(string $fields, array $values=null, string $where=null, bool $debug=false){
        $fields_T="";
        $atributos = explode(",", $fields);

        for($i=0; $i<count($atributos); $i++) $fields_T.=", $atributos[$i] = ?";
        $fields_T = substr($fields_T,2);
        $sql = "UPDATE ".$this->tableName." SET $fields_T";
        if(isset($where)) $sql .= " WHERE $where";
        if ($debug){echo $sql; var_dump($values); exit;}
        $t=$this->updateDB($sql,$values);
        return $t;
    }

    /**
     * @param array|null $values
     * @param string|null $where
     * @param bool $debug
     * @return bool|void
     */
    public function delete(array $values=null, string $where=null, bool $debug=false){
        $sql = "DELETE FROM ".$this->tableName;
        if(isset($where)) $sql .= " WHERE $where";
        if ($debug){echo $sql; exit;}
        $t=$this->deleteDB($sql,$values);
        return $t;
    }

    /**
     * @return false|string
     */
    public function lastInsertId(){
        return $this->lastId();
    }

}