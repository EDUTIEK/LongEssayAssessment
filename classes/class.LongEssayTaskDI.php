<?php

namespace ILIAS\Plugin\LongEssayTask;


use ILIAS\Plugin\LongEssayTask\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorRepository;
use ILIAS\Plugin\LongEssayTask\Data\DataService;
use ILIAS\Plugin\LongEssayTask\Data\EssayDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\ObjectDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\ObjectRepository;
use ILIAS\Plugin\LongEssayTask\Data\TaskDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\TaskRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterRepository;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LongEssayTaskDI
{
    protected static $instance;
    protected $object;
    protected $task;
    protected $essay;
    protected $writer;
    protected $corrector;
    protected $dataServices = [];
    protected $correctorAdminServices = [];

    protected function __construct()
    {
    }

    public static function getInstance(): LongEssayTaskDI
    {

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return ObjectRepository
     */
    public function getObjectRepo(): ObjectRepository
    {
        if ($this->object === null) {
            $this->object = new ObjectDatabaseRepository();
        }

        return $this->object;
    }

    /**
     * @return TaskRepository
     */
    public function getTaskRepo(): TaskRepository
    {
        if ($this->task === null) {
            $this->task = new TaskDatabaseRepository();
        }

        return $this->task;
    }

    /**
     * @return EssayRepository
     */
    public function getEssayRepo(): EssayRepository
    {
        if ($this->essay === null) {
            $this->essay = new EssayDatabaseRepository();
        }

        return $this->essay;
    }

    /**
     * @return WriterRepository
     */
    public function getWriterRepo(): WriterRepository
    {
        if ($this->writer === null) {
            $this->writer = new WriterDatabaseRepository();
        }

        return $this->writer;
    }

    /**
     * @return CorrectorRepository
     */
    public function getCorrectorRepo(): CorrectorRepository
    {
        if ($this->corrector === null) {
            $this->corrector = new CorrectorDatabaseRepository();
        }

        return $this->corrector;
    }

    /**
     * @param int $task_id
     * @return DataService
     */
    public function getDataService(int $task_id) : DataService
    {
        if (!isset($this->dataServices[$task_id])) {
            $this->dataServices[$task_id] = new DataService($task_id);
        }
        return $this->dataServices[$task_id];
    }

    /**
     * @param int $task_id
     * @return CorrectorAdminService
     */
    public function getCorrectorAdminService(int $task_id) : CorrectorAdminService
    {
        if (!isset($this->correctorAdminServices[$task_id])) {
            $this->correctorAdminServices[$task_id] = new CorrectorAdminService($task_id);
        }
        return $this->correctorAdminServices[$task_id];
    }

}