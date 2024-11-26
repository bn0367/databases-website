<?php

global $serverName, $dbName, $user, $pw;

$serverName = "10.43.207.109";#"http://database-postgresql.cs311.svc.cluster.local";
$dbName = "art";
$user = "postgres";
$pw = getenv("POSTGRES_PASSWORD");
$userID = 38;