<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data;

use ilContext;

/**
 * Base class of a database repository handling RecordData objects
 * @see RecordData
 */
abstract class RecordRepo
{
    protected \ilDBInterface $db;
    protected \ilLogger $logger;

    /**
     * Echo all read actions if called from the console
     * logging is determined by the log level
     */
    private $echoReadActions = false;

    /**
     * Echo all write actions if called from the console
     * logging is determined by the log level
     */
    private $echoWriteActions = false;

    /**
     * Cached query results
     * @var RecordData[][]  query hash => recordData[]
     */
    private $recordCache = [];

    /**
     * Cached checks for existence
     * @var bool[]      query hash => record exists
     */
    private $boolCache = [];

    /**
     * Cache of integers as query results
     * @var integer[]   query hash => int
     */
    private $integerCache = [];


    /**
     * Cache of integers lists as query results
     * @var integer[][]   query hash => int[]
     */
    private $integerListCache = [];


    /**
     * Cache of string lists as query results
     * @var string[][]   query hash => string[]
     */
    private $stringListCache = [];


    /**
     * Constructor
     */
    public function __construct(\ilDBInterface $db, \ilLogger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Check if a query has a record
     */
    protected function hasRecord(string $query, $useCache = true) : bool
    {
        $hash = md5($query);
        if ($useCache && isset($this->boolCache[$hash])) {
            return $this->boolCache[$hash];
        }
        $result = $this->db->query($query);
        $exists = !empty($this->db->fetchAssoc($result));

        if ($useCache) {
            $this->boolCache[$hash] = $exists;
        }
        return $exists;
    }

    /**
     * Count the records of a query
     * The query must deliver one field with the counter value
     */
    protected function countRecords(string $query, $useCache = true) : int
    {
        $hash = md5($query);
        if ($useCache && isset($this->integerCache[$hash])) {
            return $this->integerCache[$hash];
        }
        $count = 0;
        $result = $this->db->query($query);
        if ($row = $this->db->fetchAssoc($result)) {
            foreach ($row as $value) {
                $count = (int) $value;
                break;
            }
        }
        if ($useCache) {
            $this->integerCache[$hash] = $count;
        }
        return $count;
    }

    /**
     * Get a list of integers from a query
     * @return int[]
     */
    protected function getIntegerList(string $query, string $key, $useCache = true) : array
    {
        $hash = md5(serialize([$query, $key]));
        if ($useCache && isset($this->integerListCache[$hash])) {
            return $this->integerListCache[$hash];
        }
        $result = $this->db->query($query);
        $list = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $list[] = (int) $row[$key];
        }
        if ($useCache) {
            $this->integerListCache[$hash] = $list;
        }
        return $list;
    }


    /**
     * Get a list of string from a query
     * @return string[]
     */
    protected function getStringList(string $query, string $key, $useCache = true) : array
    {
        $hash = md5(serialize([$query, $key]));
        if ($useCache && isset($this->stringListCache[$hash])) {
            return $this->stringListCache[$hash];
        }
        $result = $this->db->query($query);
        $list = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $list[] = (string) $row[$key];
        }
        if ($useCache) {
            $this->stringListCache[$hash] = $list;
        }
        return $list;
    }


    /**
     * Get the record objects for standard tables
     * The tables should be short enough to get all records
     * @param RecordData $model model of the data objects that should get the query results
     * @param bool $useCache cache the resulting records of exactly this query
     * @param bool $forceIndex force using the record key as array index, even if it is composed of several fields
     * @return RecordData[]
     */
    protected function getAllRecords(RecordData $model, $useCache = true, $forceIndex = false) : array
    {
        $query = "SELECT * FROM " . $this->db->quoteIdentifier($model::tableName());
        return $this->queryRecords($query, $model, $useCache, $forceIndex);
    }

    /**
     * Get a single record from a query
     * Optionally provide a default instance
     */
    protected function getSingleRecord(string $query, RecordData $model, ?RecordData $default = null, $useCache = true) : ?RecordData
    {
        foreach ($this->queryRecords($query, $model, $useCache) as $record) {
            return $record;
        }
        return $default;
    }


    /**
     * Query for records
     * If the model has a single key field then this field value is used as the array index
     * @param string $query  SQL query
     * @param RecordData $model model of the data objects that should get the query results
     * @param bool $useCache cache the resulting records of exactly this query
     * @param bool $forceIndex force using the record key as array index, even if it is composed of several fields
     * @param ?string $indexKey use a specific row field as array index (useful if all records for a part of the key are queried)
     * @return RecordData[]     key value => RecordData
     */
    protected function queryRecords(string $query, RecordData $model, bool $useCache = true, bool $forceIndex = false, ?string $indexKey = null) : array
    {
        $hash = md5(serialize([$query, $forceIndex, $indexKey]));
        if ($useCache && isset($this->recordCache[$hash])) {
            return $this->recordCache[$hash];
        }

        $hasSingleKey = (count($model::tableKeyTypes()) == 1);

        $records = [];
        $result = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($result)) {
            $record = $model::from($row);
            $this->logAction('READ', $record);
            if (isset($indexKey)) {
                $records[$row[$indexKey]] = $record;
            }
            elseif ($hasSingleKey || $forceIndex) {
                $records[$record->key()] = $record;
            }
            else {
                $records[] = $record;
            }
        }

        if ($useCache) {
            $this->recordCache[$hash] = $records;
        }
        return $records;
    }

    /**
     * Insert the records
     * @return RecordData  the inserted record (eventually wit the new sequence number)
     */
    protected function insertRecord(RecordData $record) : RecordData
    {
        if ($record::tableHasSequence() && empty($record->sequence())) {
            $record = $record->withTableSequence((int) $this->db->nextId($record::tableName()));
        }
        $types = array_merge($record::tableKeyTypes(), $record::tableOtherTypes());
        $fields = $this->getFieldsArray($record, $types, false);
        $this->logAction('INSERT', $record);
        $this->db->insert($record::tableName(), $fields);
        return $record;
    }

    /**
     * Insert or update the record
     * @return RecordData  the inserted or updated record (eventually wit the new sequence number)
     */
    protected function replaceRecord(RecordData $record) : RecordData
    {
        if ($record::tableHasSequence() && empty($record->sequence())) {
            $record = $record->withTableSequence((int) $this->db->nextId($record::tableName()));
        }
        $key_fields = $this->getFieldsArray($record, $record::tableKeyTypes());
        $other_fields = $this->getFieldsArray($record, $record::tableOtherTypes());
        $this->logAction('REPLACE', $record);
        $this->db->replace($record::tableName(), $key_fields, $other_fields);
        return $record;
    }

    /**
     * Update a record
     */
    protected function updateRecord(RecordData $record)
    {
        $key_fields = $this->getFieldsArray($record, $record::tableKeyTypes());
        $other_fields = $this->getFieldsArray($record, $record::tableOtherTypes());
        $this->logAction('UPDATE', $record);
        $this->db->update($record::tableName(), array_merge($key_fields, $other_fields), $key_fields);
    }

    /**
     * Delete a record
     */
    protected function deleteRecord(RecordData $record)
    {
        $conditions = [];
        foreach($this->getFieldsArray($record, $record::tableKeyTypes()) as $quotedKey => $field) {
            $conditions[] = $quotedKey . " = " . $this->db->quote($field[1], $field[0]);
        }
        $query = "DELETE FROM " . $this->db->quoteIdentifier($record::tableName())
            . " WHERE " . implode(" AND ", $conditions);
        $this->logAction('DELETE', $record);
        $this->db->manipulate($query);
    }

    /**
     * Get the typed field values
     * @param RecordData $record
     * @param array $types  field name => type
     * @param bool $quoteNames
     * @return array    (quoted) field name => [type, value]
     */
    protected function getFieldsArray(RecordData $record, array $types, bool $quoteNames = true) : array
    {
        $fields = [];
        foreach ($record->row() as $key => $value) {
            if (isset($types[$key])) {
                $fields[$quoteNames ? $this->db->quoteIdentifier($key) : $key] = [$types[$key], $value];
            }
        }
        return $fields;
    }

    /**
     * Log a database action for the record
     * @param string     $action
     * @param RecordData $record
     */
    protected function logAction(string $action, RecordData $record)
    {
        $entry = $action . ' '. get_class($record) . ' | ' . $record->info();
        if (!ilContext::usesHTTP()) {
            if ($this->echoReadActions && $action == 'READ') {
                echo $entry . "\n";
            }
            if ($this->echoWriteActions &&  $action != 'READ') {
                echo $entry . "\n";
            }
        }

        if ($this->logger->isHandling(\ilLogLevel::DEBUG)) {
            $entry = $action . ' ' . get_class($record) . ' | ' . $record->debug();
            //$this->logger->debug($entry);
        }
        if ($this->logger->isHandling(\ilLogLevel::INFO)) {
            $entry = $action . ' '. get_class($record) . ' | ' . $record->info();
            //$this->logger->info($entry);
        }
    }
}