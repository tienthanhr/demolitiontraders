<?php
// scripts/clean_csv_column_count.php
$input = __DIR__ . '/../export_table_products_cat_11Dec2025_12_19.csv';
$output = __DIR__ . '/../export_table_products_cat_11Dec2025_12_19_cleaned.csv';

$in = fopen($input, 'r');
$out = fopen($output, 'w');

$header = fgetcsv($in);
$expected = count($header);
fputcsv($out, $header);
$line = 2;
while (($row = fgetcsv($in)) !== false) {
    $count = count($row);
    if ($count < $expected) {
        // Pad with empty fields
        $row = array_pad($row, $expected, '');
    } elseif ($count > $expected) {
        // Trim extra fields
        $row = array_slice($row, 0, $expected);
    }
    fputcsv($out, $row);
    $line++;
}
fclose($in);
fclose($out);
echo "Cleaned CSV written to $output\n";
