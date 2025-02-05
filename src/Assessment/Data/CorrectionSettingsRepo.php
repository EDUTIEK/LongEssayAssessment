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

use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class CorrectionSettingsRepo implements \Edutiek\AssessmentService\Assessment\Data\CorrectionSettingsRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): CorrectionSettings
    {
        return $this->repo->new();
    }

    public function one(int $ass_id): ?CorrectionSettings
    {
        return $this->repo->queryOneBy(['ass_id' => $ass_id]);
    }

    public function save(CorrectionSettings $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $ass_id): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
    }
}
