<?php

namespace Tests\Unit;

use IRIS\CustomCSVImporter;
use PDO;
use PHPUnit\Framework\TestCase;

class CustomCSVImporterTest extends TestCase
{
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '1';
    private $database = 'test_iris';

    /** @var  PDO */
    private $dbh;

    public function setUp()
    {
        $this->dbh = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->database . '', $this->user, $this->pass);

        $this->cleanUp();
        parent::setUp();
    }

    private function cleanUp()
    {
        $this->dbh->exec('
        SET FOREIGN_KEY_CHECKS = 0; 
        TRUNCATE table batches; 
        TRUNCATE table card_types; 
        TRUNCATE table merchants; 
        TRUNCATE table transaction_types; 
        TRUNCATE table transactions; 
        SET FOREIGN_KEY_CHECKS = 1;
        ');
    }

    public function test_must_throw_exception_if_headers_is_not_valid()
    {
        $this->expectException(\InvalidArgumentException::class);

        $mapping = [
            CustomCSVImporter::MERCHANT_ID             => 'Merchant ID',
            CustomCSVImporter::MERCHANT_NAME           => 'Merchant Name',
            CustomCSVImporter::BATCH_DATE              => 'Batch Date',
            CustomCSVImporter::BATCH_REF_NUM           => 'Batch Reference Number',
            CustomCSVImporter::TRANSACTION_DATE        => 'Transaction Date',
            CustomCSVImporter::TRANSACTION_TYPE        => 'Transaction Type',
            CustomCSVImporter::TRANSACTION_CARD_TYPE   => 'Transaction Card Type',
            CustomCSVImporter::TRANSACTION_CARD_NUMBER => 'Transaction Card Number',
            CustomCSVImporter::TRANSACTION_AMOUNT      => 'Transaction222 Amount'
        ];

        $path = \dirname(__DIR__) . '/files/headers.csv';

        (new CustomCSVImporter($path, $mapping))->run();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function test_must_work_if_all_headers_present()
    {
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

        $path = \dirname(__DIR__) . '/files/headers.csv';

        (new CustomCSVImporter($path, $mapping))->run();
    }

    public function test_must_save_all_data_to_database()
    {
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

        $path = \dirname(__DIR__) . '/files/report.csv';

        (new CustomCSVImporter($path, $mapping))->run();

        $data = $this->dbh->query('SELECT * FROM transactions');
        $transactions = $data->fetchAll(PDO::FETCH_ASSOC);

        $data = $this->dbh->query('SELECT * FROM batches');
        $batches = $data->fetchAll(PDO::FETCH_ASSOC);

        $data = $this->dbh->query('SELECT * FROM merchants');
        $merchants = $data->fetchAll(PDO::FETCH_ASSOC);

        $data = $this->dbh->query('SELECT * FROM transaction_types');
        $transactionsTypes = $data->fetchAll(PDO::FETCH_ASSOC);

        $data = $this->dbh->query('SELECT * FROM card_types');
        $cardTypes = $data->fetchAll(PDO::FETCH_ASSOC);

        $this->assertEquals($this->getTestTransactions(), $transactions);
        $this->assertEquals($this->getTestBatches(), $batches);
        $this->assertEquals($this->getTestMerchants(), $merchants);
        $this->assertEquals($this->getTestTransactionTypes(), $transactionsTypes);
        $this->assertEquals($this->getTestCardTypes(), $cardTypes);
    }

    /**
     * @return array
     */
    private function getTestTransactions()
    {
        return [
            [
                'id'           => '1',
                'batch_id'     => '1',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '803158******3281',
                'amount'       => '20.94',
            ],
            [
                'id'           => '2',
                'batch_id'     => '1',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '922314******7463',
                'amount'       => '92.73',
            ],
            [
                'id'           => '3',
                'batch_id'     => '1',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '742388******8047',
                'amount'       => '31.29',
            ],
            [
                'id'           => '4',
                'batch_id'     => '2',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '821278******8615',
                'amount'       => '64.98',
            ],
            [
                'id'           => '5',
                'batch_id'     => '2',
                'type_id'      => '1',
                'card_type_id' => '2',
                'date'         => '2018-05-04',
                'card_number'  => '909582******9260',
                'amount'       => '4.04',
            ],
            [
                'id'           => '6',
                'batch_id'     => '2',
                'type_id'      => '1',
                'card_type_id' => '2',
                'date'         => '2018-05-04',
                'card_number'  => '308958******7061',
                'amount'       => '31.97',
            ],
            [
                'id'           => '7',
                'batch_id'     => '3',
                'type_id'      => '1',
                'card_type_id' => '2',
                'date'         => '2018-05-04',
                'card_number'  => '774118******2006',
                'amount'       => '32.85',
            ],
            [
                'id'           => '8',
                'batch_id'     => '4',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '437054******9193',
                'amount'       => '68.26',
            ],
            [
                'id'           => '9',
                'batch_id'     => '4',
                'type_id'      => '2',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '877098******7670',
                'amount'       => '-56.27',
            ],
            [
                'id'           => '10',
                'batch_id'     => '4',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '498879******3771',
                'amount'       => '59.62',
            ],
            [
                'id'           => '11',
                'batch_id'     => '4',
                'type_id'      => '1',
                'card_type_id' => '3',
                'date'         => '2018-05-04',
                'card_number'  => '607735******0567',
                'amount'       => '11.13',
            ],
            [
                'id'           => '12',
                'batch_id'     => '4',
                'type_id'      => '1',
                'card_type_id' => '1',
                'date'         => '2018-05-04',
                'card_number'  => '150304******4396',
                'amount'       => '70.74',
            ],
            [
                'id'           => '13',
                'batch_id'     => '4',
                'type_id'      => '1',
                'card_type_id' => '3',
                'date'         => '2018-05-04',
                'card_number'  => '613570******2082',
                'amount'       => '68.66',
            ],
        ];
    }

    private function getTestBatches()
    {
        return [
            [
                'id'          => '1',
                'date'        => '2018-05-05',
                'ref_num'     => '307965163216534420635657',
                'merchant_id' => '344858307505959269',
            ],
            [
                'id'          => '2',
                'date'        => '2018-05-05',
                'ref_num'     => '713911985564755663442139',
                'merchant_id' => '344858307505959269',
            ],

            [
                'id'          => '3',
                'date'        => '2018-05-05',
                'ref_num'     => '32021449192915738278018',
                'merchant_id' => '344858307505959269',
            ],
            [
                'id'          => '4',
                'date'        => '2018-05-05',
                'ref_num'     => '865311392860455095554114',
                'merchant_id' => '79524081202206784',
            ],
        ];
    }

    private function getTestMerchants()
    {
        return [
            [
                'id'   => '79524081202206784',
                'name' => 'Merchant #79524081202206784',
            ],
            [
                'id'   => '344858307505959269',
                'name' => 'Merchant #344858307505959269',
            ],
        ];
    }

    private function getTestTransactionTypes()
    {
        return [
            [
                'id'   => '2',
                'name' => 'Refund',
            ],
            ['id'   => '1',
             'name' => 'Sale',
            ],
        ];
    }

    private function getTestCardTypes()
    {
        return [
            [
                'id'   => '3',
                'name' => 'AX',
            ],
            [
                'id'   => '2',
                'name' => 'DC',
            ],
            [
                'id'   => '1',
                'name' => 'VI',
            ],
        ];
    }

}
