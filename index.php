<?php

global $serverName, $dbName, $user, $pw;
include "vars.php";

try {
    print("<!DOCTYPE html><html lang='en'><head><title>View Table</title><link rel='stylesheet' href='style.css'></head><body><a href='index.html'>Back</a>");
    $table = $_POST['table'];

    if (!in_array($table, ["artwork", "artist", "dimensions", "metadata"])) {
        print("Invalid table name.</body></html>");
        die();
    }

    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName", $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    # bad
    $stmt = $conn->prepare("SELECT * FROM " . $table . " LIMIT 100");
    $stmt->execute();
    print("<table><tr>");
    for ($i = 0; $i < $stmt->columnCount(); $i++) {
        $col = $stmt->getColumnMeta($i);
        print("<th>" . $col["name"] . "</th>");
    }
    print("</tr>");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print("<tr>");
        foreach ($row as $key => $value) {
            print("<td>" . $value . "</td>");
        }
        print("</tr>");
    }
    print("</table></body></html>");
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}