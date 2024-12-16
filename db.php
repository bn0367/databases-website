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
    $dimension_query = "";

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
        if ($date_end < 0) {
            $date_end *= -1;
            $date_end = sprintf("%04d", $date_end);
            $date_end .= '-12-31';
            $date_end .= ' BC';
        } else {
            $date_end = sprintf("%04d", $date_end);
            $date_end .= '-12-31';
        }

        $date_query_param = '(, \'' . $date_end . '\']';
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    } else if (!empty($_POST['date-search-start'])) {
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
        $date_query_param = '[\'' . $date_start . '\',)';
        $date_query = "AND ? " . $range_operator . " date_range";
        $any_query = true;
        $params[] = $date_query_param;
    }
    if (empty($_POST['fuzzy-search']) || !($_POST['fuzzy-search'] === "on")) {
        $date_query .= " AND NOT date_fuzzy";
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
    $dim_from = [];
    $dim_to = [];
    for ($i = 1; $i < 9; $i++) {
        if (!empty($_POST["dimensions-search$i"])) {
            if ($i < 5) {
                $dim_from[] = $_POST["dimensions-search$i"];
            } else {
                $dim_to[] = $_POST["dimensions-search$i"];
            }
        }
    }
    if (count($dim_from) > 0 && count($dim_to) > 0) {
        $dims = min(count($dim_from), count($dim_to));
        $dimension_query = "AND (";
        $checks = [];
        for ($i = 0; $i < $dims; $i++) {
            $checks[] = "(dimension = " . ($i + 1) . " AND amount BETWEEN ? AND ?)";
            $params[] = $dim_from[$i];
            $params[] = $dim_to[$i];
        }
        $dimension_query .= implode(" OR ", $checks);
        $dimension_query .= ") GROUP BY artwork.id, title, artist.name, date_range HAVING COUNT(*) >= $dims";
        $any_query = true;
    } else if (count($dim_from) > 0) {
        $dims = count($dim_from);
        $dimension_query = "AND (";
        $checks = [];
        for ($i = 0; $i < $dims; $i++) {
            $checks[] = "(dimension = " . ($i + 1) . " AND amount >= ?)";
            $params[] = $dim_from[$i];
        }
        $dimension_query .= implode(" OR ", $checks);
        $dimension_query .= ") GROUP BY artwork.id, title, artist.name, date_range HAVING COUNT(*) >= $dims";
        $any_query = true;
    } else if (count($dim_to) > 0) {
        $dims = count($dim_to);
        $dimension_query = "AND (";
        $checks = [];
        for ($i = 0; $i < $dims; $i++) {
            $checks[] = "(dimension = " . ($i + 1) . " AND amount <= ?)";
            $params[] = $dim_to[$i];
        }
        $dimension_query .= implode(" OR ", $checks);
        $dimension_query .= ") GROUP BY artwork.id, title, artist.name, date_range HAVING COUNT(*) >= $dims";
        $any_query = true;
    }

    if (!$any_query) {
        print('{"status": "error", error: "No search text provided!"}');
        die();
    }
    if (!$any_metadata) {
        $metadata_query = "";
    }
    $query_text = "
SELECT DISTINCT artwork.id, title, artist.name, date_range
FROM artwork JOIN artist ON artwork.artist_id = artist.id JOIN metadata on artwork.id = metadata.id JOIN dimensions ON dimensions.id = metadata.dimension_id
WHERE 1=1 $date_query $basic_query $metadata_query $dimension_query";
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
            $escaped_value = str_replace(["\\'", "’"], '\'', $escaped_value);
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
    print('{"status": "error", "error": "' . addslashes($e->getMessage()) . '"}');
}