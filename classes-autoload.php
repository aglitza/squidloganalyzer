<?php

spl_autoload_register(function ($className) {
    $sFullPathFile = __DIR__ . "/classes/" . strtolower($className) . ".class.php";

    // Check if file exists
    if (file_exists($sFullPathFile)) {
        require_once $sFullPathFile;
    } else {
        die("Fehler: Die Klasse $className konnte nicht geladen werden. Datei $sFullPathFile fehlt.");
    }
});
