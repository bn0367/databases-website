<?php

global $serverName, $dbName, $user, $pw;
include "vars.php";

try {
    if (isset($_POST['basic-search'])) {
        $search = $_POST['basic-search'];
    } else {
        print("{status: 'error', error: 'No search text provided!'}");
        die();
    }
    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName", $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $stmt = $conn->prepare("SELECT artwork.id, title, artist.name, date_range 
FROM artwork JOIN artist ON artwork.artist_id = artist.id 
WHERE websearch_to_tsquery(:query) @@ vectorized OR artist.name LIKE '%:query%'");
    $stmt->execute(["query" => $search]);

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    print("{\"status\": \"success\", \"objects\": [");
    $last = array_search(end($rows), $rows);
    foreach ($rows as $row) {
        print("{");
        $last = array_search(end($row), $row);
        foreach ($row as $key => $value) {
            $escaped_value = addslashes($value);
            $escaped_key = addslashes($key);
            print("\"$key\": \"$value\"");
            print(next($row) ? "," : "");
        }
        print("}");
        print(next($rows) ? "," : "");
    }
    print("]}");
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}