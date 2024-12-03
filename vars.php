<?php

global $serverName, $dbName, $user, $pw;

$serverName = "database-postgresql";
$dbName = "art";
$user = "postgres";
$pw = getenv("POSTGRES_PASSWORD");
