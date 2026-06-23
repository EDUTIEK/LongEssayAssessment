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

use Edutiek\AssessmentService\EssayTask\Data\MarkedPdf;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class MarkedPdfRepo implements \Edutiek\AssessmentService\EssayTask\Data\MarkedPdfRepo
{
    public function __construct(private readonly RepositoryInterface $repo)
    {
    }

    public function new(): MarkedPdf
    {
        return $this->repo->new();
    }

    public function oneByIds(int $task_id, int $writer_id, ?int $corrector_id): ?MarkedPdf
    {
        return $this->repo->queryOneBy(['task_id' => $task_id, 'writer_id' => $writer_id, 'corrector_id' => $corrector_id]);
    }

    /** @return string[] */
    public function allFileIds(): array
    {
        return array_merge(
            $this->repo->queryStrings("SELECT own_pdf FROM " . $this->repo->table() . " WHERE own_pdf IS NOT NULL", 'own_pdf'),
            $this->repo->queryStrings("SELECT sum_pdf FROM " . $this->repo->table() . " WHERE sum_pdf IS NOT NULL", 'sum_pdf')
        );
    }

    public function allByTaskId(int $task_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id]);
    }

    public function allByWriterId(int $writer_id): array
    {
        return $this->repo->queryAllBy(['writer_id' => $writer_id]);
    }

    public function allByCorrectorId(int $corrector_id): array
    {
        return $this->repo->queryAllBy(['corrector_id' => $corrector_id]);
    }

    public function allByTaskIdAndWriterId(int $task_id, int $writer_id): array
    {
        return $this->repo->queryAllBy(['task_id' => $task_id, 'writer_id' => $writer_id]);
    }

    public function save(MarkedPdf $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }
}
