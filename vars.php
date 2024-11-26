<?php

global $serverName, $dbName, $user, $pw;

$serverName = "http://database-postgresql.cs311.svc.cluster.local";
$dbName = "art";
$user = "postgres";
$pw = getenv("postgres-password");
$userID = 38;
?>