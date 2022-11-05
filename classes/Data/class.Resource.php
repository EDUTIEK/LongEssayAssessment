<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
class Resource extends ActivePluginRecord
{
    const RESOURCE_TYPE_FILE = 'file';
    const RESOURCE_TYPE_URL = 'url';

    const RESOURCE_AVAILABILITY_BEFORE = 'before';  // before writing
    const RESOURCE_AVAILABILITY_DURING = 'during';  // after writing start, unlimited
    const RESOURCE_AVAILABILITY_AFTER = 'after';    // for review period

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_resource';

    /**
     * Essay id
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       true
     * @con_sequence         true
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $id;

    /**
     * The task_id currently corresponds to the obj_id of the ILIAS object
     *
     * @var integer
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       true
     * @con_fieldtype        integer
     * @con_length           4
     */
    protected $task_id = 0;


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           255
     */
    protected $title = "";


    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       false
     * @con_fieldtype        clob
     */
    protected $description = null;

    /**
     * The file_id
     *
     * @var string
     * @con_has_field        true
     * @con_is_primary       false
     * @con_sequence         false
     * @con_is_notnull       false
     * @con_fieldtype        text
     * @con_length           50
     */
    protected $file_id = "";

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           4000
     */
    protected $url = "";

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           10
     */
    protected $type = self::RESOURCE_TYPE_URL;

    /**
     * @var string
     * @con_has_field        true
     * @con_is_notnull       true
     * @con_fieldtype        text
     * @con_length           10
     */
    protected $availability = self::RESOURCE_AVAILABILITY_BEFORE;

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