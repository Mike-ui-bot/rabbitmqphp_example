#!/usr/bin/php
<?php

$db = new mysqli("localhost", "tester_user", "testMe", "IT490");

if ($db->errno != 0)
{
        echo "Failed to connect to database: ". $db->error . PHP_EOL;
        exit(0);
}

echo "Successfully connected to database" . PHP_EOL;

return $db;
?>

