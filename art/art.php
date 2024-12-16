<?php

global $serverName, $dbName, $user, $pw;
include "../vars.php";

try {
    if (!isset($_POST['id'])) {
        print("{status: 'error', error: 'No id provided!'}");
        die();
    }
    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName", $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT artwork.id, title, name as artist, date_range as date, culture, category, type, materials, description FROM artwork JOIN artist ON artwork.artist_id = artist.id JOIN metadata ON artwork.id = metadata.id WHERE artwork.id = :id");
    $stmt->execute(array(
        "id" => $_POST['id'],
    ));

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print("{\"status\": \"success\", \"objects\": [");
    $row_strs = [];
    foreach ($rows as $row) {
        $row_str = "{";
        $values = [];
        foreach ($row as $key => $value) {
            $escaped_value = addslashes($value);
            $escaped_value = str_replace(["\n", "\r"], "", $escaped_value);
            $escaped_value = str_replace("\\'", '\'', $escaped_value);
            $escaped_key = addslashes($key);
            $values[] = "\"$escaped_key\": \"$escaped_value\"";
        }
        $row_str .= implode(",", $values);
        $row_str .= "}";
        $row_strs[] = $row_str;
    }
    print(implode(",", $row_strs));
    print("]}");
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}