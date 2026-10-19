<?php

const CONFIG_DATA_LAYER = [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "dbname" => "",
    "username" => "",
    "passwd" => "",
    "options" => [
        // Charset: prefira UTF8MB4 em "SET NAMES" ("UTF8" no MySQL é utf8mb3 e não grava emoji).
        // PDO::ATTR_EMULATE_PREPARES => false usa prepares nativos do servidor (mais seguro e com tipos
        // reais), mas não permite repetir o mesmo :nome na consulta e pode mudar tipos retornados
        // quando ATTR_STRINGIFY_FETCHES não estiver ativo — teste a aplicação antes de ativar.
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '-03:00'; SET NAMES UTF8MB4, lc_time_names = 'pt_BR'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
        PDO::ATTR_STRINGIFY_FETCHES => true
    ],
    "homologation" => "homologacao",
    // true/false define explicitamente o ambiente (ou use a env DB_HOMOLOGATION=1).
    // Sem esta chave, cai na detecção legada por $_SERVER['SERVER_NAME'], que pode vir do header Host.
    "homologation_active" => false,
    "directory_models" => "App\\Models\\",
    "return_error_json" => false,
    "display_errors_details" => true
];