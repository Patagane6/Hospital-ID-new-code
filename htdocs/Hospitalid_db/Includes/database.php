<?php
    // Use 127.0.0.1 to force TCP (avoids socket "No such file or directory" errors)
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database_name = "g12.it1_patagane-gelaga-pelegrino";
    $port = 3306;

    // enable mysqli exceptions for clearer errors during development
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // attempt connection using TCP (explicit port) but connect without selecting
    // the database first so we can create/import it if missing.
    $conn = null;
    try {
        $conn = mysqli_connect($servername, $username, $password, null, $port);
    } catch (Throwable $e) {
        die('Database server connection failed to ' . $servername . ':' . $port . ' - ' . $e->getMessage());
    }

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Check if the database exists
    $db_exists = false;
    try {
        $res = mysqli_query($conn, "SHOW DATABASES LIKE '" . mysqli_real_escape_string($conn, $database_name) . "'");
        if ($res && mysqli_num_rows($res) > 0) {
            $db_exists = true;
        }
    } catch (Throwable $e) {
        // ignore here; will attempt to create below
    }

    // If DB doesn't exist, attempt to create it and import schema from SQL file
    if (!$db_exists) {
        // create database
        try {
            mysqli_query($conn, "CREATE DATABASE `" . str_replace('`','``',$database_name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            die('Failed to create database ' . $database_name . ' - ' . $e->getMessage());
        }

        // import schema if SQL file exists
        $sql_file = __DIR__ . '/../hospitalid_db.sql';
        if (file_exists($sql_file) && is_readable($sql_file)) {
            $sql_contents = file_get_contents($sql_file);
            if ($sql_contents !== false && trim($sql_contents) !== '') {
                // select the new database then run the multi-query
                mysqli_select_db($conn, $database_name);
                if (!mysqli_multi_query($conn, $sql_contents)) {
                    // collect the error
                    $err = mysqli_error($conn);
                    die('Failed to import database schema: ' . $err);
                }
                // ensure all results are consumed
                do { if ($res = mysqli_store_result($conn)) { mysqli_free_result($res); } } while (mysqli_more_results($conn) && mysqli_next_result($conn));
            }
        }
    }

    // finally select the database for normal use
    if (!mysqli_select_db($conn, $database_name)) {
        throw new RuntimeException('Could not select database ' . $database_name . ' - ' . mysqli_error($conn));
    }

