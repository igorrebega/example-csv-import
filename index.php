<?php

use IRIS\CustomCSVImporter;

require __DIR__ . '/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = __DIR__ . '/loremB.csv';

$mapping = [
    CustomCSVImporter::MERCHANT_ID             => 'Merchant ID',
    CustomCSVImporter::MERCHANT_NAME           => 'Merchant Name',
    CustomCSVImporter::BATCH_DATE              => 'Batch Date',
    CustomCSVImporter::BATCH_REF_NUM           => 'Batch Reference Number',
    CustomCSVImporter::TRANSACTION_DATE        => 'Transaction Date',
    CustomCSVImporter::TRANSACTION_TYPE        => 'Transaction Type',
    CustomCSVImporter::TRANSACTION_CARD_TYPE   => 'Transaction Card Type',
    CustomCSVImporter::TRANSACTION_CARD_NUMBER => 'Transaction Card Number',
    CustomCSVImporter::TRANSACTION_AMOUNT      => 'Transaction Amount'
];

(new CustomCSVImporter($path, $mapping))->run();