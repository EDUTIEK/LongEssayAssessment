<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EditorHistory extends ActivePluginRecord
{
    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_editor_history';

	/**
	 * Editor history id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       true
	 * @con_sequence         true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $id;

	/**
	 * The Essay Id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $essay_id;


	/**
	 * Timestamp (datetime)
	 *
	 * @var string|null
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        timestamp
	 */
	protected ?string $timestamp = null;

	/**
	 * Content Text (richtext)
	 *
	 * @var null|string
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        clob
	 */
	protected ?string $content = null;

	/**
	 * is delta
	 * @var bool
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected bool $is_delta= false;

	/**
	 * hash before
	 *
	 * @var string
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       false
	 * @con_fieldtype        text
	 * @con_length           50
	 */
	protected string $hash_before;

	/**
	 * hash before
	 *
	 * @var string
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       false
	 * @con_fieldtype        text
	 * @con_length           50
	 */
	protected string $hash_after;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return EditorHistory
	 */
	public function setId(int $id): EditorHistory
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getEssayId(): int
	{
		return $this->essay_id;
	}

	/**
	 * @param int $essay_id
	 * @return EditorHistory
	 */
	public function setEssayId(int $essay_id): EditorHistory
	{
		$this->essay_id = $essay_id;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getTimestamp(): ?string
	{
		return $this->timestamp;
	}

	/**
	 * @param string|null $timestamp
	 * @return EditorHistory
	 */
	public function setTimestamp(?string $timestamp): EditorHistory
	{
		$this->timestamp = $timestamp;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getContent(): ?string
	{
		return $this->content;
	}

	/**
	 * @param string|null $content
	 * @return EditorHistory
	 */
	public function setContent(?string $content): EditorHistory
	{
		$this->content = $content;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isIsDelta(): bool
	{
		return $this->is_delta;
	}

	/**
	 * @param bool $is_delta
	 * @return EditorHistory
	 */
	public function setIsDelta(bool $is_delta): EditorHistory
	{
		$this->is_delta = $is_delta;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHashBefore(): string
	{
		return $this->hash_before;
	}

	/**
	 * @param string $hash_before
	 * @return EditorHistory
	 */
	public function setHashBefore(string $hash_before): EditorHistory
	{
		$this->hash_before = $hash_before;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHashAfter(): string
	{
		return $this->hash_after;
	}

	/**
	 * @param string $hash_after
	 * @return EditorHistory
	 */
	public function setHashAfter(string $hash_after): EditorHistory
	{
		$this->hash_after = $hash_after;
		return $this;
	}
}