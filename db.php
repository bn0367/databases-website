<?php

global $serverName, $dbName, $user, $pw;
include "vars.php";

try {
    $conn = new PDO("pgsql:host=$serverName;dbname=$dbName", $user, $pw);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $range_operator = "@>";


    $date_query = "";
    $basic_query = "";
    $metadata_query = "";

    $any_query = false;
    $any_metadata = false;

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
        if ($date_start < 0) {
            $date_start *= -1;
            $date_start = sprintf("%04d", $date_start);
            $date_start .= '-01-01';
            $date_start .= ' BC';
        } else {
            $date_start = sprintf("%04d", $date_start);
            $date_start .= '-01-01';
        }
        $date_end = $_POST['date-search-end'];
        if ($date_end < 0) {
            $date_end *= -1;
            $date_end = sprintf("%04d", $date_end);
            $date_end .= '-12-31';
            $date_end .= ' BC';
        } else {
            $date_end = sprintf("%04d", $date_end);
            $date_end .= '-12-31';
        }

        $date_query_param = '[\'' . $date_start . '\', \'' . $date_end . '\']';
        file_put_contents('php://stderr', print_r($date_query_param . "\n", TRUE));
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    } else if (!empty($_POST['date-search-end'])) {
        $date_end = $_POST['date-search-end'];
        $date_query_param = '(, \'' . sprintf('%04d', $date_end) . '-12-31\']\'';
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    } else if (!empty($_POST['date-search-start'])) {
        $date_start = $_POST['date-search-start'];
        $date_query_param = '[\'' . sprintf('%04d', $date_start) . '-01-01\',)';
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
    if (!empty($_POST['culture-search'])) {
        $culture = $_POST['culture-search'];
        $culture = '%' . $culture . '%';
        $metadata_query = "AND culture::TEXT ILIKE ? ";
        $params[] = $culture;
        $any_query = true;
        $any_metadata = true;
    }
    if (!empty($_POST['category-search'])) {
        $category = $_POST['category-search'];
        $category = '%' . $category . '%';
        $metadata_query .= "AND category::TEXT ILIKE ? ";
        $params[] = $category;
        $any_query = true;
        $any_metadata = true;
    }
    if (!empty($_POST['type-search'])) {
        $type = $_POST['type-search'];
        $type = '%' . $type . '%';
        $metadata_query .= "AND type::TEXT ILIKE ? ";
        $params[] = $type;
        $any_query = true;
        $any_metadata = true;
    }
    if (!empty($_POST['materials-search'])) {
        $materials = $_POST['materials-search'];
        $materials = '%' . $materials . '%';
        $metadata_query .= "AND materials::TEXT ILIKE ? ";
        $params[] = $materials;
        $any_query = true;
        $any_metadata = true;
    }
    if (!$any_query) {
        print("{status: 'error', error: 'No search text provided!'}");
        die();
    }
    if (!$any_metadata) {
        $metadata_query = "";
    }
    $query_text = "
SELECT artwork.id, title, artist.name, date_range
FROM artwork JOIN artist ON artwork.artist_id = artist.id JOIN metadata on artwork.id = metadata.id
WHERE 1=1 $date_query $basic_query $metadata_query LIMIT 20";
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
            $escaped_value = str_replace(["\n", "\r"], "", $escaped_value);
            $escaped_value = str_replace("\\'", '\'', $escaped_value);
            $escaped_key = addslashes($key);
            $values[] = "\"$escaped_key\": \"$escaped_value\"";
        }
        $row_str .= implode(",", $values);
        $row_str .= "}";
        $row_strs[] = $row_str;
    }
    $json_str .= implode(",", $row_strs);
    $json_str .= "]}";
    print($json_str);
} catch (PDOException $e) {
    print("Internal Server Error: " . $e->getMessage());
}