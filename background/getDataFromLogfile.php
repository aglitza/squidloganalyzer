<?php

// Load configuration file(s)
include __DIR__ . "/../includes/configuration.includes.php";
include __DIR__ . "/../classes-autoload.php";

$oDatabase = new Database();
$oGeneral = new General();

echo date("Y-m-d H:i:s") . " Hier wird der Datenimport aus der Logdatei behandelt.\n";
$sSourceFile = $sFilesPath . "access_log.csv";

// Load data from the import file into the database
$oGeneral->loadData($sSourceFile);

// Close the database connection
$oDatabase->closeConnection();
?>