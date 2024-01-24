<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Task;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class Resource extends RecordData
{
    const RESOURCE_TYPE_FILE = 'file';
    const RESOURCE_TYPE_URL = 'url';
    const RESOURCE_TYPE_INSTRUCTION = 'instruct';
    const RESOURCE_TYPE_SOLUTION  = 'solution';

    const RESOURCE_AVAILABILITY_BEFORE = 'before';  // before writing
    const RESOURCE_AVAILABILITY_DURING = 'during';  // after writing start, unlimited
    const RESOURCE_AVAILABILITY_AFTER = 'after';    // for review period

    protected const tableName = 'xlas_resource';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'task_id'=> 'integer',
        'title' => 'text',
        'description' => 'text',
        'file_id' => 'text',
        'url' => 'text',
        'type' => 'text',
        'availability' => 'text'
    ];

    protected int $id = 0;
    protected int $task_id = 0;
    protected string $title = "";
    protected ?string $description = null;
    protected string $file_id = "";
    protected string $url = "";
    protected string $type = self::RESOURCE_TYPE_URL;
    protected string $availability = self::RESOURCE_AVAILABILITY_BEFORE;

    public static function model()
    {
        return new self();
    }


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Resource
     */
    public function setId(int $id): Resource
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return Resource
     */
    public function setTaskId(int $task_id): Resource
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Resource
     */
    public function setTitle(string $title): Resource
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Resource
     */
    public function setDescription(?string $description): Resource
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileId(): string
    {
        return $this->file_id;
    }

    /**
     * @param string $file_id
     * @return Resource
     */
    public function setFileId(string $file_id): Resource
    {
        $this->file_id = $file_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Resource
     */
    public function setUrl(string $url): Resource
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Resource
     */
    public function setType(string $type): Resource
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getAvailability(): string
    {
        return $this->availability;
    }

    /**
     * @param string $availability
     * @return Resource
     */
    public function setAvailability(string $availability): Resource
    {
        $this->availability = $availability;
        return $this;
    }
}
