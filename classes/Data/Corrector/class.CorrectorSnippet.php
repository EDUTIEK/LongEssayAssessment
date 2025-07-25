<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Data\Corrector;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

class CorrectorSnippet  extends RecordData
{
    protected const tableName = 'xlas_corr_snippet';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'task_id' => 'integer',
        'corrector_id' => 'integer',
        'key' => 'text',
        'purpose' => 'text',
        'text' => 'text'
    ];

    protected int $id = 0;
    protected int $task_id = 0;
    protected int $corrector_id = 0;
    protected int $resource_id = 0;
    protected string $key = '';
    protected string $purpose = '';
    protected ?string $text = null;

    public static function model(): CorrectorSnippet
    {
        return new self();
    }

    public function getTaskId(): int
    {
        return $this->task_id;
    }

    public function setTaskId(int $task_id): CorrectorSnippet
    {
        $this->task_id = $task_id;
        return $this;
    }

    public function getCorrectorId(): int
    {
        return $this->corrector_id;
    }

    public function setCorrectorId(int $corrector_id): CorrectorSnippet
    {
        $this->corrector_id = $corrector_id;
        return $this;
    }

    public function getResourceId(): int
    {
        return $this->resource_id;
    }

    public function setResourceId(int $resource_id): CorrectorSnippet
    {
        $this->resource_id = $resource_id;
        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): CorrectorSnippet
    {
        $this->key = $key;
        return $this;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): CorrectorSnippet
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): CorrectorSnippet
    {
        $this->text = $text;
        return $this;
    }

}