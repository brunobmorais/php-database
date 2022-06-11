<?php
namespace BMorais\Database;
/**
 * CLASSE BANCO
 *  Esta classe faz conexão com o banco de dados mysql utilizando o pdo
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @version 2
 * @copyright GPL © 2021, bmorais.com
 * @date 2022-05-18
 * @package php
 * @subpackage class
 * @access private
 */


use PDO;
use PDOException;


class Connect
{

    /** @var PDOException */
    private static $error;

    /** @var PDO */
    private static $instance;

    /**
     * Connect constructor.
     */
    final private function __construct()
    {
    }
    /**
     * Connect clone.
     */
    private function __clone()
    {
    }

    public static function getInstance($database=CONFIG_DATA_LAYER["dbname"]):?PDO
    {
        if (!isset (self::$instance)) {

            try {
                self::$instance = new PDO(CONFIG_DATA_LAYER["driver"] . ":host=" . CONFIG_DATA_LAYER["host"] . ";dbname=" . $database . ";port=" . CONFIG_DATA_LAYER["port"],
                    CONFIG_DATA_LAYER["username"],
                    CONFIG_DATA_LAYER["passwd"],
                    CONFIG_DATA_LAYER["options"]);
            } catch (PDOException $e) {
                self::setError($e);
            }
        }

        return self::$instance;
    }

    /**
     * @return PDOException|null
     */
    public static function getError(): ?PDOException
    {
        return self::$error;
    }

    public static function setError(PDOException $e, string $sql=''){
        self::$error = $e;
        $message = array();
        $obj = array();

        $message["ARQUIVO"] =  $e->getFile();
        $message["SQL"] = $sql;
        $message["LINHA"] = $e->getLine();
        $message["MENSAGEM"]= $e->getMessage();
        $message["INFORMACOES"]= $e->getMessage() . " / " . $e->getCode() . " / " . $e->getPrevious() . " / " . $e->getTraceAsString();

        if (CONFIG_DATA_LAYER["display_errors_details"]) {
            $obj = [
                "error" => true,
                "message" => "Ops, tivemos um erro na base de dados, tente mais tarde",
                "code" => "500",
                "exception" => $message
            ];
        } else {
            $obj = [
                "error" => true,
                "message" => "Ops, tivemos um erro na base de dados, tente mais tarde",
                "code" => "500",
            ];
        }
        echo json_encode($obj);
        die;
    }
}

