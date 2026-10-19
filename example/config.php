<?php

const CONFIG_DATA_LAYER = [
    "driver" => "mysql",
    "host" => "localhost",
    "port" => "3306",
    "dbname" => "",
    "username" => "",
    "passwd" => "",
    "options" => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8, lc_time_names = 'pt_BR'",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
        PDO::ATTR_STRINGIFY_FETCHES => true
    ],
    "homologation" => "homologacao",
    // true/false define explicitamente o ambiente (ou use a env DB_HOMOLOGATION=1).
    // Sem esta chave, cai na detecção legada por $_SERVER['SERVER_NAME'], que pode vir do header Host.
    "homologation_active" => false,
    "directory_models" => "App\\Models\\",
    "display_errors_details" => true,
];