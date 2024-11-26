<?php

global $serverName, $dbName, $user, $pw;
include "vars.php";

try {
    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName",
        $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM artwork LIMIT 100");
    $stmt->execute();

    print("<table>");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print("<tr><td>");
        foreach ($row as $key => $value) {
            print($value);
        }
        print("</td></tr>");
    }
    print("</table>");
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}
?>