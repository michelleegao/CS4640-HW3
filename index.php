<?php

session_start();
require_once __DIR__ . "/src/AnagramsGameController.php";

$controller = new AnagramsGameController();
$controller->run();

?>