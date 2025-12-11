<?php
// scripts/check_csv_column_count.php
$csv = fopen(__DIR__ . '/../export_table_products_cat_11Dec2025_12_19.csv', 'r');
$header = fgetcsv($csv);
$expected = count($header);
$line = 2;
while (($row = fgetcsv($csv)) !== false) {
    if (count($row) !== $expected) {
        echo "Line $line: Expected $expected columns, found ".count($row)."\n";
    }
    $line++;
}
fclose($csv);
echo "Check complete.\n";
