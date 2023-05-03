<?php
declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Data;

/**
 * Base class for representing objects of database records
 * The functions defined in this base class are intended to be used by the corresponding repository
 *
 * Requirements for child classes:
 * The properties must be protected, not private
 * The property names must exactly match the database field names given in the constants keyTypes ans otherTypes
 * Their types must be compatible with the database field types
 * If the database field allows null, then the property must also allow null
 *
 * @see RecordRepo
 * @todo: replace 'static' type hints with return types in PHP 8
 */
abstract class RecordData
{
    /**
     * Name of the database table, must be overridden
     */
    protected const tableName = '';

    /**
     * Table uses a sequence, must be overridden
     */
    protected const hasSequence = false;

    /**
     * ilDBInterface types of the primary key fields, must be overridden
     * key field name =>  type
     */
    protected const keyTypes = [];

    /**
     * Get the ilDBInterface types of the other fields, must be overridden
     * other field name =>  type
     */
    protected const otherTypes = [];

    /**
     * Get an example instance with default values
     * Used to provide an argument for RecordRepo::queryRecords()
     * @see RecordRepo::queryRecords()
     * @return static
     */
    abstract public static function model();

    /**
     * Get an instance with the single row data of a database query
     * This will only work if the property names and types match the constants keyTypes and otherTypes
     * @param array $row assoc row data from the query
     * @param string $prefix prefix for all row keys of this record in the result of a join, e.g. 'course_'
     * @return static
     */
    public static function from(array $row, string $prefix = '')
    {
        $instance = static::model();
        foreach (array_merge(static::tableKeyTypes(), static::tableOtherTypes()) as $key => $type) {
            if (isset($row[$prefix . $key])) {
                switch ($type) {
                    case 'text':
                    case 'date':
                    case 'time':
                    case 'timestamp':
                    case 'clob':
                        $instance->$key = (string) $row[$prefix . $key];
                        break;
                    case 'integer':
                        $instance->$key = (int) $row[$prefix . $key];
                        break;
                    case 'float':
                        $instance->$key = (float) $row[$prefix . $key];
                        break;
                    default:
                        $instance->$key = $row[$prefix . $key];
                }
            }
            else {
                $instance->$key = null;
            }
        }
        return $instance;
    }

    /**
     * Get the database row data from an instance
     * This will only work if the property names and types match the constants keyTypes and otherTypes
     * @return array
     */
    public function row() : array
    {
        $row = [];
        foreach (array_merge(static::tableKeyTypes(), static::tableOtherTypes()) as $key => $type) {
            $row[$key] = $this->$key;
        }
        return $row;
    }

    /**
     * Get a representation of the table key that can be used as an array index
     * @return mixed
     */
    public function key()
    {
        $keyvals = [];
        foreach (static::tableKeyTypes() as $key => $type) {
            $keyvals[] = $this->$key;
        }

        if (count($keyvals) == 1) {
            // return a single key with unchanged type
            return $keyvals[0];
        }
        else {
            // return a composite key as serialized string
            return serialize($keyvals);
        }
    }

    /**
     * Get a hash of the whole record data which is stored in the database
     * This can be used to compare two records
     */
    public function hash() : string
    {
        $values = [];
        foreach (static::tableKeyTypes() as $key => $type) {
            $values[] = $this->$key;
        }
        foreach (static::tableOtherTypes() as $key => $type) {
            $values[] = $this->$key;
        }
        return md5(serialize($values));
    }

    /**
     * Get the sequence value (if a sequence exists)
     * Assume that a record with sequence has only one integer key
     */
    public function sequence() : ?int
    {
        if (static::tableHasSequence()) {
            $key = array_keys(static::tableKeyTypes())[0];
            return $this->$key;
        }
        return null;
    }

    /**
     * Set a sequence value
     * Assume that a record with sequence has only one integer key
     */
    public function setTableSequence(int $value) : void
    {
        if (static::tableHasSequence()) {
            $key = array_keys(static::tableKeyTypes())[0];
            $this->$key = $value;
        }
    }

    /**
     * Get the name of the database table
     */
    public static function tableName() : string
    {
        return static::tableName;
    }

    /**
     * Get if the table uses a sequence
     */
    public static function tableHasSequence() : bool
    {
        return static::hasSequence;
    }

    /**
     * Get the ilDBInterface types of the primary key fields
     * @return array    key field name =>  type
     */
    public static function tableKeyTypes() : array
    {
        return static::keyTypes;
    }

    /**
     * Get the ilDBInterface types of the other fields
     *  @return array   other field name =>  type
     */
    public static function tableOtherTypes() : array
    {
        return static::otherTypes;
    }

    /**
     * Provide a string of important properties for logging with info level
     * The string should be short enough to be inserted in a log line
     * The class name does not need to be included
     * @return string
     */
    public function info() : string
    {
        $parts = [];
        foreach (array_merge(static::tableKeyTypes(), static::tableOtherTypes()) as $key => $type) {
            $parts[] = $key . ': ' . $this->$key;
        }
        $info = implode(' | ', $parts);
        if (strlen($info) > 200) {
            $info = substr($info, 0, 147) . '...';
        }
        return $info;
    }

    /**
     *  Provide a string of all properties for logging with debug level
     * @return string
     */
    public function debug() : string
    {
        return print_r($this, true);
    }
}