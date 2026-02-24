<?php
$conn = pg_connect("host=localhost port=5432 dbname=practice_db user=postgres password=1234");

if (!$conn) {
    die("Connection failed.");
}
echo "Connected successfully!";
?>