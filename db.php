<?php

global $serverName, $dbName, $user, $pw;
include "vars.php";

try {
    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName", $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $range_operator = "@>";


    $date_query = "";
    $basic_query = "";

    $any_query = false;

    $search = "";
    $date_start = "";
    $date_end = "";
    $date_query_param = "";

    $params = array();
    if (isset($_POST['partial-date'])) {
        $partial_date = $_POST['partial-date'];
        if ($partial_date == "on") {
            $range_operator = "&&";
        }
    }
    if (!empty($_POST['date-search-start']) && !empty($_POST['date-search-end'])) {
        $date_start = $_POST['date-search-start'];
        $date_end = $_POST['date-search-end'];
        $date_query_param = '[\'' . $date_start . '-01-01\', \'' . $date_end . '-12-31\']';
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    } else if (!empty($_POST['date-search-end'])) {
        $date_end = $_POST['date-search-end'];
        $date_query_param = '(, \'' . $date_end . '-12-31\']\'';
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    } else if (!empty($_POST['date-search-start'])) {
        $date_start = $_POST['date-search-start'];
        $date_query_param = '[\'' . $date_start . '-01-01\',)';
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    }
    if (!empty($_POST['basic-search'])) {
        $search = $_POST['basic-search'];
        $search_query_param = '\'' . addslashes($search) . '\'';
        $basic_query = "AND websearch_to_tsquery(?) @@ vectorized";
        $any_query = true;
        $params[] = $search_query_param;
    }
    if (!$any_query) {
        print("{status: 'error', error: 'No search text provided!'}");
        die();
    }
    $query_text = "
SELECT artwork.id, title, artist.name, date_range
FROM artwork JOIN artist ON artwork.artist_id = artist.id 
WHERE 1=1 $date_query $basic_query";
    file_put_contents('php://stderr', print_r($query_text . "\n", TRUE));
    $stmt = $conn->prepare($query_text);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json; charset=utf-8');
    $json_str = "{\"status\": \"success\", \"objects\": [";
    $row_strs = [];
    foreach ($rows as $row) {
        $row_str = "{";
        $values = [];
        foreach ($row as $key => $value) {
            $escaped_value = addslashes($value);
            $escaped_key = addslashes($key);
            $values[] = "\"$key\": \"$value\"";
        }
        $row_str .= implode(",", $values);
        $row_str .= "}";
        $row_strs[] = $row_str;
    }
    $json_str .= implode(",", $row_strs);
    $json_str .= "]}";
    $json_str = str_replace(["\n", "\\n"], "", $json_str);
    print($json_str);
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}