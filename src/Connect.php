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
        if (CONFIG_DATA_LAYER["return_error_json"]) {
            $obj = [
                "error" => true,
                "message" => "Ooops! Aconteceu algo inesperado, tente mais tarde! Nossa equipe já foi informada",
                "code" => "500",
            ];

            if (CONFIG_DATA_LAYER["display_errors_details"] ?? true) {
                $obj["sql"] = $sql;
                $obj["exception"] = $e;
            }

            echo json_encode($obj);
        } else {
            $message = "<h4>Ooops! Aconteceu algo inesperado, tente mais tarde! Nossa equipe já foi informada</h5><hr>";
            $message .= "<p><b>File:</b>  " . $e->getFile() . "<br/>";
            $message .= "<b>SQL:</b>  " . $sql . "<br/>";
            $message .= "<b>Line:</b>  " . $e->getLine() . "<br/>";
            $message .= "<b>Message:</b>  " . $e->getMessage() . "<br/>";
            $message .= "<b>Exception:</b>" . $e->getCode() . "<br/>" . $e->getPrevious() . "<br/>" . $e->getTraceAsString() . "<br/></p>";

            if (CONFIG_DATA_LAYER["display_errors_details"]) {
                echo $message;
            } else {
                echo "<h4>Ooops! Aconteceu algo inesperado, tente mais tarde! Nossa equipe já foi informada</h5><hr>";
            }
        }
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

