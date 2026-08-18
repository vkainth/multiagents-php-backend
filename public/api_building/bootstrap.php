<?php
require 'vendor/autoload.php';
use Dotenv\Dotenv;

use Src\System\DatabaseConnector;

$dotenv = new DotEnv(__DIR__);
$dotenv->load();

$dbConnection = (new DatabaseConnector())->getConnection();
$dbConnectionMLS = (new DatabaseConnector())->getConnectionMLS();
$dbConnectionLES = (new DatabaseConnector())->getConnectionLES();
