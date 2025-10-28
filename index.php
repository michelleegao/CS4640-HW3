<?php

// Authors: Michelle Gao (bnm5cm) and Henna Panjshiri (kew4bd)
// Published links:
// https://cs4640.cs.virginia.edu/bnm5cm/CS4640-HW3
// https://cs4640.cs.virginia.edu/kew4bd/hw3/index.php

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