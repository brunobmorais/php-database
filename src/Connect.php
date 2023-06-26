<?php

namespace BMorais\Database;

/**
 * CLASS CONNECT
 * This class connects to the mysql database using pdo
 *
 * @author Bruno Morais <brunomoraisti@gmail.com>
 * @copyright MIT, bmorais.com
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
     * @param string $database
     * @return PDO|null
     */
    public static function getInstance($database): ?PDO
    {
        try {
            $instance = new PDO(
                CONFIG_DATA_LAYER['driver'] . ':host=' . CONFIG_DATA_LAYER['host'] . ';dbname=' . $database . ';port=' . CONFIG_DATA_LAYER['port'],
                CONFIG_DATA_LAYER['username'],
                CONFIG_DATA_LAYER['passwd'],
                CONFIG_DATA_LAYER['options']
            );
            return $instance;
        } catch (PDOException $e) {
            self::setError($e);
            return null;
        }
    }

    /**
     * @param PDOException $e
     * @param string $sql
     * @return void
     */
    public static function setError(PDOException $e, string $sql = '')
    {
        self::$error = $e;
        if (CONFIG_DATA_LAYER['return_error_json']) {
            $obj = [
                'error' => true,
                'message' => 'Ooops! ERRO',
                'code' => '500',
            ];

            if (CONFIG_DATA_LAYER['display_errors_details'] ?? true) {
                $obj['sql'] = $sql;
                $obj['exception'] = $e;
            }

            echo json_encode($obj);
        } else {
            $message = '<h4>Ooops! ERRO</h5><hr>';
            $message .= '<p><b>File:</b>  ' . $e->getFile() . '<br/>';
            $message .= '<b>SQL:</b>  ' . $sql . '<br/>';
            $message .= '<b>Line:</b>  ' . $e->getLine() . '<br/>';
            $message .= '<b>Message:</b>  ' . $e->getMessage() . '<br/>';
            $message .= '<b>Exception:</b>' . $e->getCode() . '<br/>' . $e->getPrevious() . '<br/>' . $e->getTraceAsString() . '<br/></p>';

            if (CONFIG_DATA_LAYER['display_errors_details']) {
                echo $message;
            } else {
                echo '<h4>Ooops! ERRO</h5><hr>';
            }
        }
    }
}
