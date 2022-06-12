<?php
namespace BMorais\Database;
/**
 * CLASSE BANCO
 *  Esta classe faz conexão com o banco de dados mysql utilizando o pdo
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright GPL © 2022, bmorais.com
 * @package bmorais\database
 * @subpackage class
 * @access private
 */


use PDO;
use PDOException;

class Connect
{

    /** @var PDOException|null */
    private static ?PDOException $error = null;

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

    /**
     * @param array|null $database
     * @return PDO|null
     */
    public static function getInstance($database=CONFIG_DATA_LAYER["dbname"]): ?PDO
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

    public static function setError(PDOException $e, string $sql = ''){
        self::$error = $e;
        $obj = array();
        if (CONFIG_DATA_LAYER["display_errors_details"]??true) {
            $obj = [
                "error" => true,
                "message" => "Ops, tivemos um erro na base de dados, tente mais tarde",
                "code" => "500",
                "exception" => $e
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

    /**
     * @return array|null
     */
    public static function getError(): ?array
    {
        return self::$error;
    }
}

