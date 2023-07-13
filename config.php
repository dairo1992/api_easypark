<?php
// Archivo: config.php

date_default_timezone_set('America/Bogota');
const BASE_URL = "http://192.168.52.14/globalsuppliessolutions";
const DB_HOST = "localhost";
const DB_NAME = "apirest";
const DB_CHARSET = "charset=utf8";
const DB_USER = "root";
const DB_PASSWORD = "";



    $connectionString = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";";
    try{
        $conect = new PDO($connectionString, DB_USER, DB_PASSWORD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
        $conect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conect;
    }catch(PDOException $e){
        $conect = 'Error de conexión';
        echo "ERROR: " . $e->getMessage();
    }

