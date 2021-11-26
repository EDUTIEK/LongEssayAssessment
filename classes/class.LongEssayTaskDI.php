<?php

namespace ILIAS\Plugin\LongEssayTask;


use ILIAS\Plugin\LongEssayTask\Data\CorrectorDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\CorrectorRepository;
use ILIAS\Plugin\LongEssayTask\Data\EssayDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\EssayRepository;
use ILIAS\Plugin\LongEssayTask\Data\ObjectRepository;
use ILIAS\Plugin\LongEssayTask\Data\TaskRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterDatabaseRepository;
use ILIAS\Plugin\LongEssayTask\Data\WriterRepository;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class LongEssayTaskDI
{
    protected ObjectRepository $object;
    protected TaskRepository $task;
	protected EssayRepository $essay;
	protected WriterRepository $writer;
	protected CorrectorRepository $corrector;

    public function getObjectRepo(): ObjectRepository
    {
        if ($this->object === null)
        {
            //$this->essay = new ObjectDatabaseRepository();
        }

        return $this->object;
    }

    public function getTaskRepo(): TaskRepository
    {
        if ($this->task === null)
        {
            //$this->essay = new TaskDatabaseRepository();
        }

        return $this->task;
    }

	public function getEssayRepo(): EssayRepository
	{
		if ($this->essay === null)
		{
			$this->essay = new EssayDatabaseRepository();
		}

		return $this->essay;
	}

    public function getWriterRepo(): WriterRepository
    {
        if ($this->writer === null)
        {
            $this->writer = new WriterDatabaseRepository();
        }

        return $this->writer;
    }

    public function getCorrectorRepo(): CorrectorRepository
    {
        if ($this->corrector === null)
        {
            $this->corrector = new CorrectorDatabaseRepository();
        }

        return $this->corrector;
    }

}