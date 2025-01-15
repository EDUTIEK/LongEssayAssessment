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

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_corr_points')]
class CorrectorPoints extends \Edutiek\AssessmentService\EssayTask\Data\CorrectorPoints
{
    #[Key]
    private int $id;
    private int $criterion_id;
    private int $corr_comment_id;
    private int $points;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getCriterionId(): int
    {
        return $this->criterion_id;
    }
    public function setCriterionId(int $criterion_id): void
    {
        $this->criterion_id = $criterion_id;
    }
    public function getCorrCommentId(): int
    {
        return $this->corr_comment_id;
    }
    public function setCorrCommentId(int $corr_comment_id): void
    {
        $this->corr_comment_id = $corr_comment_id;
    }
    public function getPoints(): int
    {
        return $this->points;
    }
    public function setPoints(int $points): void
    {
        $this->points = $points;
    }
}
