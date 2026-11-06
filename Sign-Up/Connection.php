<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "kairos_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $exception) {
    error_log("Database connection failed: " . $exception->getMessage());
    throw new RuntimeException("Unable to connect to the database.", 0, $exception);
}
?>