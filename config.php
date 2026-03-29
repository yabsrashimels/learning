<?php

$conn = pg_connect("
host=dpg-d74helcr85hc73fqek10-a
port=5432
dbname=simple_oco2
user=simple_oco2_user
password=920TuuIu3Dmpw7PrE794bn6T6EfBPfZq
");

if (!$conn) {
    echo "Database connection failed";
}
