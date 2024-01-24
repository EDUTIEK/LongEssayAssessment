<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class AccessToken extends RecordData
{
    /**
     * The token ist
     */
    public const PURPOSE_DATA = 'data';
    public const PURPOSE_FILE = 'file';

    protected const tableName = 'xlas_access_token';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'user_id' => 'integer',
        'task_id' => 'integer',
        'purpose' => 'text',
        'token' => 'text',
        'ip' => 'datetime',
        'valid_until' => 'datetime'
    ];

    protected int $id = 0;
    protected int $user_id = 0;
    protected int $task_id = 0;
    protected string $purpose = "";
    protected string $token = "";
    protected string $ip = "";
    protected ?string $valid_until = null;

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
    public function getTaskId(): int
    {
        return $this->task_id;
    }

    /**
     * @param int $task_id
     * @return AccessToken
     */
    public function setTaskId(int $task_id): AccessToken
    {
        $this->task_id = $task_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPurpose(): string
    {
        return $this->purpose;
    }

    /**
     * @param string $purpose
     * @return AccessToken
     */
    public function setPurpose(string $purpose): AccessToken
    {
        switch ($purpose) {
            case self::PURPOSE_DATA:
            case self::PURPOSE_FILE:
                $this->purpose = $purpose;
                break;
        }

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
