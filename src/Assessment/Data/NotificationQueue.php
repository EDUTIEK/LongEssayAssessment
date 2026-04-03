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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use DateTimeImmutable;
use Edutiek\AssessmentService\Assessment\Data\NotificationType;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Sequence;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;
use ILIAS\UI\Component\Input\Field\DateTime;

#[Table(name: 'xlas_as_noti_queue')]
class NotificationQueue extends \Edutiek\AssessmentService\Assessment\Data\NotificationQueue
{
    #[Key]
    #[Sequence]
    private int $id = 0;
    private int $ass_id = 0;
    private int $user_id = 0;
    private string $type = NotificationType::WRITER_CORRECTION_FINALIZED->value;
    private ?DateTimeImmutable $added = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getAssId(): int
    {
        return $this->ass_id;
    }
    public function setAssId(int $ass_id): self
    {
        $this->ass_id = $ass_id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getType(): NotificationType
    {
        return NotificationType::tryFrom($this->type) ?? NotificationType::WRITER_CORRECTION_FINALIZED;
    }

    public function setType(NotificationType $type): self
    {
        $this->type = $type->value;
        return $this;
    }

    public function getAdded(): ?DateTimeImmutable
    {
        return $this->added;
    }

    public function setAdded(?DateTimeImmutable $added): self
    {
        $this->added = $added;
        return $this;
    }
}
