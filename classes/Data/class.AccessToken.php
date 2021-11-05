<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class AccessToken extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_access_token';

    /**
     * alert id
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
	 * The ILIAS user id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $user_id;

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
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           50
	 */
	protected string $token = "";

	/**
	 * @var string
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        text
	 * @con_length           15
	 */
	protected string $ip = "";

	/**
	 * Valid Until (datetime)
	 *
	 * @var string|null
	 * @con_has_field        true
	 * @con_is_notnull       false
	 * @con_fieldtype        timestamp
	 */
	protected ?string $valid_until = null;

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return AccessToken
	 */
	public function setId(int $id): AccessToken
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getUserId(): int
	{
		return $this->user_id;
	}

	/**
	 * @param int $user_id
	 * @return AccessToken
	 */
	public function setUserId(int $user_id): AccessToken
	{
		$this->user_id = $user_id;
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
	 * @return AccessToken
	 */
	public function setEssayId(int $essay_id): AccessToken
	{
		$this->essay_id = $essay_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken(): string
	{
		return $this->token;
	}

	/**
	 * @param string $token
	 * @return AccessToken
	 */
	public function setToken(string $token): AccessToken
	{
		$this->token = $token;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIp(): string
	{
		return $this->ip;
	}

	/**
	 * @param string $ip
	 * @return AccessToken
	 */
	public function setIp(string $ip): AccessToken
	{
		$this->ip = $ip;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getValidUntil(): ?string
	{
		return $this->valid_until;
	}

	/**
	 * @param string|null $valid_until
	 * @return AccessToken
	 */
	public function setValidUntil(?string $valid_until): AccessToken
	{
		$this->valid_until = $valid_until;
		return $this;
	}
}