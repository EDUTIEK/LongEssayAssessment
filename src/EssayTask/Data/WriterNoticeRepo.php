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

use Edutiek\AssessmentService\EssayTask\Data\WriterNotice;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class WriterNoticeRepo implements \Edutiek\AssessmentService\EssayTask\Data\WriterNoticeRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): WriterNotice
    {
        return $this->repo->new();
    }

    public function oneByEssayIdAndNo(int $id): ?WriterNotice
    {
        return $this->repo->queryOneBy(['id' => $id, 'note_no' => true]);
    }

    public function allByEssayId(int $essay_id): array
    {
        return $this->repo->queryAllBy(['essay_id' => $essay_id]);
    }

    public function save(WriterNotice $entity): void
    {
        $this->repo->replace($entity);
    }

    public function deleteByEssayId(int $essay_id): void
    {
        $this->repo->deleteAllBy(['essay_id' => $essay_id]);
    }
}
