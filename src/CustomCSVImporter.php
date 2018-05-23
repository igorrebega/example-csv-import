<?php

namespace IRIS;

use InvalidArgumentException;

class CustomCSVImporter
{
    const MERCHANT_ID = 0; // digits only, up to 18 digits
    const MERCHANT_NAME = 1; // string, max length - 100
    const BATCH_DATE = 2; // YYYY-MM-DD
    const BATCH_REF_NUM = 3; // digits only, up to 24 digits
    const TRANSACTION_DATE = 4; // YYYY-MM-DD
    const TRANSACTION_TYPE = 5; // string, max length - 20
    const TRANSACTION_CARD_TYPE = 6; // string, max length - 2, possible values - VI/MC/AX and so on
    const TRANSACTION_CARD_NUMBER = 7; // string, max length - 20
    const TRANSACTION_AMOUNT = 8; // amount, negative values are possible

    const TRANSACTIONS_PER_TIME = 10000;

    /**
     * @var resource
     */
    private $file;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var array
     */
    private $rows = [];

    /**
     * @var array
     */
    private $transactionTypes = [];

    /**
     * @var array
     */
    private $cardTypes = [];

    /**
     * @var array
     */
    private $lastBatches = [];

    /**
     * @var array
     */
    private $merchantsToSave = [];

    /**
     * @var array
     */
    private $batchesToSave = [];

    /**
     * @var array
     */
    private $cardTypesToSave = [];

    /**
     * @var array
     */
    private $transactionTypesToSave = [];

    /**
     * @var array
     */
    private $transactionsToSave = [];

    /**
     * @var Database
     */
    private $database;

    /**
     * CustomCSVImporter constructor.
     * @param string $path
     * @param array $mapping
     * @throws \InvalidArgumentException
     */
    public function __construct($path, array $mapping)
    {
        $this->database = new Database();

        $this->openFile($path);
        $this->saveHeaders($mapping);
    }

    /**
     * Run import
     */
    public function run()
    {
        $this->loadBaseDataFromDB();

        while ($this->getColumnsBatch()) {
            $this->getColumnsBatch();
        }
    }

    /**
     * Do one iteration of reading
     *
     * @return bool
     */
    private function getColumnsBatch()
    {
        $this->resetSaveVariables();

        $count = 0;
        $this->rows = [];

        while (false !== ($data = fgetcsv($this->file)) && $count < self::TRANSACTIONS_PER_TIME) {
            $this->rows[] = $data;
            $count++;
        }

        if (empty($this->rows)) {
            return false;
        }

        $this->prepareRelationData();
        $this->saveRelationData();
        $this->prepareTransactionData();
        $this->saveTransactions();

        return true;
    }

    /**
     * So we save only data from current iteration
     */
    private function resetSaveVariables()
    {
        $this->transactionsToSave = [];
        $this->batchesToSave = [];
        $this->cardTypesToSave = [];
        $this->transactionTypesToSave = [];
        $this->merchantsToSave = [];
    }

    /**
     * Prepare relations for db saving
     */
    private function prepareRelationData()
    {
        foreach ($this->rows as $row) {
            $this->prepareBatchesToSave($row);
            $this->prepareCardTypesToSave($row);
            $this->prepareMerchantToSave($row);
            $this->prepareTransactionTypesToSave($row);
        }
    }

    /**
     * Prepare transaction for db saving
     */
    private function prepareTransactionData()
    {
        foreach ($this->rows as $row) {
            $this->storeTransactionForSave($row);
        }
    }

    /**
     * Write transaction data to property
     * @param $row
     * @throws \InvalidArgumentException
     */
    private function storeTransactionForSave($row)
    {
        $transactionType = $this->getColumnFromData($row, self::TRANSACTION_TYPE);
        $cardType = $this->getColumnFromData($row, self::TRANSACTION_CARD_TYPE);
        $batchDate = $this->getColumnFromData($row, self::BATCH_DATE);
        $batchRefNum = $this->getColumnFromData($row, self::BATCH_REF_NUM);
        $merchantId = $this->getColumnFromData($row, self::MERCHANT_ID);

        $this->transactionsToSave[] = [
            'date'         => $this->getColumnFromData($row, self::TRANSACTION_DATE),
            'card_number'  => $this->getColumnFromData($row, self::TRANSACTION_CARD_NUMBER),
            'amount'       => $this->getColumnFromData($row, self::TRANSACTION_AMOUNT),
            'card_type_id' => $this->getCardTypeIdByName($cardType),
            'type_id'      => $this->getTransactionTypeIdByName($transactionType),
            'batch_id'     => $this->getBatchId($batchDate, $batchRefNum, $merchantId)
        ];
    }

    /**
     * @param string $name
     * @return integer
     */
    private function getCardTypeIdByName($name)
    {
        return $this->cardTypes[$name]['id'];
    }

    /**
     * @param string $name
     * @return integer
     */
    private function getTransactionTypeIdByName($name)
    {
        return $this->transactionTypes[$name]['id'];
    }

    /**
     * @param $date
     * @param $refNum
     * @param $merchantId
     * @return integer
     * @throws \InvalidArgumentException
     */
    private function getBatchId($date, $refNum, $merchantId)
    {
        $id = $this->generateBatchId($date, $refNum, $merchantId);

        if (!isset($this->lastBatches[$id]['id'])) {
            throw new InvalidArgumentException('Those transactions already imported');
        }

        return $this->lastBatches[$id]['id'];
    }

    /**
     * If database is not empty we load card types, transaction types and last batch from database
     */
    private function loadBaseDataFromDB()
    {
        $this->cardTypes = $this->database->selectCardTypes();

        $this->transactionTypes = $this->database->selectTransactionTypes();

        $batch = $this->database->selectLastBatch();

        if (!$batch) {
            return;
        }

        $id = $this->generateBatchId($batch['date'], $batch['ref_num'], $batch['merchant_id']);
        $this->lastBatches[$id] = $batch;
    }

    /**
     * Save relations to database
     */
    private function saveRelationData()
    {
        $this->database->insertMultiple('merchants', $this->merchantsToSave);

        $this->insertCartTypes();

        $this->insertTransactionTypes();

        $this->insertBatchesToDatabase();
    }

    /**
     * Save transaction to database
     */
    private function saveTransactions()
    {
        $this->database->insertMultiple('transactions', $this->transactionsToSave);
    }

    /**
     * Insert card types to database and save them with ids to local memory
     */
    private function insertCartTypes()
    {
        $this->database->insertMultiple('card_types', $this->cardTypesToSave);
        $ids = $this->database->getLastInsertIds();

        $index = 0;
        foreach ($this->cardTypesToSave as $key => $cardType) {
            $this->cardTypes[$cardType['name']] = [
                'id'   => $ids[$index],
                'name' => $cardType['name'],
            ];

            $index++;
        }
    }

    /**
     * Insert transaction types to database and save them with ids to local memory
     */
    private function insertTransactionTypes()
    {
        $this->database->insertMultiple('transaction_types', $this->transactionTypesToSave);
        $ids = $this->database->getLastInsertIds();

        $index = 0;
        foreach ($this->transactionTypesToSave as $key => $transactionType) {
            $this->transactionTypes[$transactionType['name']] = [
                'id'   => $ids[$index],
                'name' => $transactionType['name'],
            ];

            $index++;
        }
    }

    /**
     * Insert batches to database and save them with ids to local memory
     */
    private function insertBatchesToDatabase()
    {
        $this->database->insertMultiple('batches', $this->batchesToSave);
        $ids = $this->database->getLastInsertIds();

        $index = 0;
        foreach ($this->batchesToSave as $key => $batch) {
            $this->lastBatches[$key] = [
                'id'      => $ids[$index],
                'date'    => $batch['date'],
                'ref_num' => $batch['ref_num']
            ];

            $index++;
        }
    }

    /**
     * @param array $data
     */
    private function prepareMerchantToSave(array $data)
    {
        $id = $this->getColumnFromData($data, self::MERCHANT_ID);
        if (isset($this->merchantsToSave[$id])) {
            return;
        }

        $this->merchantsToSave[$id] = [
            'id'   => $id,
            'name' => $this->getColumnFromData($data, self::MERCHANT_NAME)
        ];
    }

    /**
     * @param array $data
     */
    private function prepareCardTypesToSave(array $data)
    {
        $name = $this->getColumnFromData($data, self::TRANSACTION_CARD_TYPE);

        if (isset($this->cardTypesToSave[$name]) || isset($this->cardTypes[$name])) {
            return;
        }

        $this->cardTypesToSave[$name] = [
            'name' => $this->getColumnFromData($data, self::TRANSACTION_CARD_TYPE)
        ];
    }

    /**
     * @param array $data
     */
    private function prepareTransactionTypesToSave(array $data)
    {
        $name = $this->getColumnFromData($data, self::TRANSACTION_TYPE);
        if (isset($this->transactionTypesToSave[$name]) || isset($this->transactionTypes[$name])) {
            return;
        }

        $this->transactionTypesToSave[$name] = [
            'name' => $this->getColumnFromData($data, self::TRANSACTION_TYPE)
        ];
    }

    /**
     * Generate unique batch id from date and ref_num
     *
     * @param $date
     * @param $refNum
     * @param $merchantId
     * @return string
     */
    private function generateBatchId($date, $refNum, $merchantId)
    {
        $data = [
            'date'        => $date,
            'ref_num'     => $refNum,
            'merchant_id' => $merchantId
        ];

        return md5(serialize($data));
    }

    /**
     * @param $data
     */
    private function prepareBatchesToSave($data)
    {
        $date = $this->getColumnFromData($data, self::BATCH_DATE);
        $refNum = $this->getColumnFromData($data, self::BATCH_REF_NUM);
        $merchantId = $this->getColumnFromData($data, self::MERCHANT_ID);

        $id = $this->generateBatchId($date, $refNum, $merchantId);

        if (isset($this->batchesToSave[$id])) {
            return;
        }

        //If we last batch is saved then previous save too - so we don`t need to save them again
        if (isset($this->lastBatches[$id])) {
            $this->batchesToSave = [];
            return;
        }

        $this->batchesToSave[$id] = [
            'date'        => $date,
            'ref_num'     => $refNum,
            'merchant_id' => $merchantId
        ];
    }

    /**
     * @param array $data
     * @param string $columnName
     * @return mixed
     */
    private function getColumnFromData(array $data, $columnName)
    {
        $index = $this->mapping[$columnName];
        return $data[$index];
    }

    /**
     * Open file and check if he exists
     * @param string $path full path to file
     * @throws \InvalidArgumentException
     */
    private function openFile($path)
    {
        $this->file = fopen($path, 'rb');
        if (!$this->file) {
            throw new InvalidArgumentException('Invalid file path');
        }
    }

    /**
     * Get headers from file and check them
     * @param array $mapping
     * @throws \InvalidArgumentException
     */
    private function saveHeaders(array $mapping)
    {
        $headers = fgetcsv($this->file);

        if (!$headers) {
            throw new InvalidArgumentException('File is not valid csv');
        }

        foreach ($mapping as $key => $name) {
            $columnIndex = array_search($name, $headers);

            if ($columnIndex === false) {
                throw new InvalidArgumentException('File don`t have all required headers');
            }

            $this->mapping[$key] = $columnIndex;
        }
    }
}