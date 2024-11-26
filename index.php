<?php

global $serverName, $dbName, $user, $pw;
include "vars.php";

try {
    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName", $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT * FROM artwork LIMIT 100");
    $stmt->execute();
    print("<table style='border: 1px solid black;border-collapse: collapse'><tr>");
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $col = $stmt->getColumnMeta($i);
        print("<th style='font-weight: bold'>" . $col["name"] . "</th>");
    }
    print("</tr>");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print("<tr style='border: 1px solid black;border-collapse: collapse'>");
        foreach ($row as $key => $value) {
            print("<td>" . $value . "</td>");
        }
        print("</tr>");
    }
    print("</table>");
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}