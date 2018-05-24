<?php
//usage php faker.php > lorem.csv

require_once 'vendor/autoload.php';
$faker = Faker\Factory::create();
$headers = [
    'Merchant ID',
    'Merchant Name',
    'Batch Date',
    'Batch Reference Number',
    'Transaction Date',
    'Transaction Type',
    'Transaction Card Type',
    'Transaction Card Number',
    'Transaction Amount'
];
$count = 1000000;
$output = '';
$csv_data = [];
$quotation_marks = '"%s"';
foreach ($headers as $header) {
    $csv_data[0][] = sprintf($quotation_marks, $header);
}

$output .= implode(',', $csv_data[0]) . "\r\n";

$k = 1;
$date = $faker->date();
for ($i = 1; $i < $count; $i++) {
    if ($i % 5 === 0) {
        $k++;
        $date = $faker->date();
    }
    $csv_data[$i][] = sprintf($quotation_marks, $k);
    $csv_data[$i][] = sprintf($quotation_marks, $faker->numberBetween(1, 1000000));
    $csv_data[$i][] = sprintf($quotation_marks, $date);
    $csv_data[$i][] = sprintf($quotation_marks, $k);
    $csv_data[$i][] = sprintf($quotation_marks, $date);
    $csv_data[$i][] = sprintf($quotation_marks, $faker->creditCardType);
    $csv_data[$i][] = sprintf($quotation_marks, $faker->creditCardType);
    $csv_data[$i][] = sprintf($quotation_marks, $faker->creditCardNumber);
    $csv_data[$i][] = sprintf($quotation_marks, $faker->randomNumber());

    $output .= implode(',', $csv_data[$i]) . "\r\n";
}
echo $output;