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

use Edutiek\AssessmentService\EssayTask\Data\WriterPrefs;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class WriterPrefsRepo implements \Edutiek\AssessmentService\EssayTask\Data\WriterPrefsRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): WriterPrefs
    {
        return $this->repo->new();
    }

    public function one(int $writer_id): ?WriterPrefs
    {
        return $this->repo->queryOneBy(['writer_id' => $writer_id]);
    }

    public function save(WriterPrefs $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $writer_id): void
    {
        $this->repo->deleteAllBy(['writer_id' => $writer_id]);
    }
}
