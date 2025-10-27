<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . "/src/AnagramsGameController.php";


$controller = new AnagramsGameController();
$controller->run();

?>