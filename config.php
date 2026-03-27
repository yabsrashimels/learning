<?php

$conn = pg_connect("
host=localhost
port=5432
dbname=simple
user=postgres
password=1234
");

if (!$conn) {
    echo "Database connection failed";
}
