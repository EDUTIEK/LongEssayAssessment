<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_ta_resource')]
class Resource extends \Edutiek\AssessmentService\Task\Data\Resource
{
    #[Key]
    private int $id = 0;
    private int $task_id = 0;
    private string $title = '';
    private ?string $description = null;
    private string $url = '';
    private string $type = '';
    private string $availability = '';
    private ?string $file_id = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getTaskId(): int
    {
        return $this->task_id;
    }
    public function setTaskId(int $task_id): void
    {
        $this->task_id = $task_id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
    public function getUrl(): string
    {
        return $this->url;
    }
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }
    public function getType(): string
    {
        return $this->type;
    }
    public function setType(string $type): void
    {
        $this->type = $type;
    }
    public function getAvailability(): string
    {
        return $this->availability;
    }
    public function setAvailability(string $availability): void
    {
        $this->availability = $availability;
    }
    public function getFileId(): ?string
    {
        return $this->file_id;
    }
    public function setFileId(?string $file_id): void
    {
        $this->file_id = $file_id;
    }
}
