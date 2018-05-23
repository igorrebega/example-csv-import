<?php

namespace IRIS;

use PDO;

class Database
{
    private $host = 'localhost';
    private $user = 'root';
    private $pass = '1';
    private $database = 'test_iris';
    private $dbh;

    private $lastInsertId;
    private $numInsertedRows = 0;

    /**
     * Database constructor.
     */
    public function __construct()
    {
        $this->dbh = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->database . '', $this->user, $this->pass);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Insert multiple rows in table
     *
     * @param $table
     * @param $rows
     */
    public function insertMultiple($table, array $rows)
    {
        $this->lastInsertId = null;
        $this->numInsertedRows = 0;

        if (empty($rows)) {
            return;
        }
        $this->dbh->beginTransaction();
        $dataFields = [];
        $questionMarks = [];

        $values = [];

        foreach ($rows as $row) {
            $questionMarks[] = '(' . $this->placeholders('?', count($row)) . ')';
            $values[] = array_values($row);
            $dataFields = array_keys($row);
        }

        $insertValues = array_merge(...$values);

        $sql = "INSERT IGNORE INTO $table (" . implode(',', $dataFields) . ") VALUES " . implode(',', $questionMarks);

        $stmt = $this->dbh->prepare($sql);

        $stmt->execute($insertValues);

        $this->lastInsertId = $this->dbh->lastInsertId();

        $this->numInsertedRows = count($values);

        $this->dbh->commit();
    }

    /**
     * @return array
     */
    public function selectCardTypes()
    {
        $sql = 'SELECT name, card_types.* from card_types';
        $stmt = $this->dbh->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

        return array_map('reset', $result);
    }

    /**
     * @return array
     */
    public function selectTransactionTypes()
    {
        $sql = 'SELECT name, transaction_types.* from transaction_types';
        $stmt = $this->dbh->query($sql);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

        return array_map('reset', $result);
    }

    /**
     * @return mixed
     */
    public function selectLastBatch()
    {
        $sql = 'SELECT * from batches ORDER BY ID DESC';
        $stmt = $this->dbh->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @return integer
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * @return array
     */
    public function getLastInsertIds()
    {
        $lastInsertIds = [];
        if ($this->lastInsertId && $this->numInsertedRows > 0) {
            $lastInsertIds = range(
                $this->lastInsertId,
                $this->lastInsertId + $this->numInsertedRows - 1
            );
        }
        return $lastInsertIds;
    }


    /**
     * @param $text
     * @param int $count
     * @param string $separator
     * @return string
     */
    private function placeholders($text, $count = 0, $separator = ',')
    {
        $result = [];
        if ($count > 0) {
            for ($x = 0; $x < $count; $x++) {
                $result[] = $text;
            }
        }

        return implode($separator, $result);
    }
}