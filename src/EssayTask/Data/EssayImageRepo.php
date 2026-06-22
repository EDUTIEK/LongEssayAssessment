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

use Edutiek\AssessmentService\EssayTask\Data\EssayImage;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ilDBInterface;

class EssayImageRepo implements \Edutiek\AssessmentService\EssayTask\Data\EssayImageRepo
{
    public function __construct(
        private readonly RepositoryInterface $repo,
        private readonly ilDBInterface $db
    ) {
    }

    public function new(): EssayImage
    {
        return $this->repo->new();
    }

    public function one(int $id): ?EssayImage
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function save(EssayImage $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    /** @return string[] */
    public function allFileIds(): array
    {
        return array_merge(
            $this->repo->queryStrings("SELECT file_id FROM " . $this->repo->table() . " WHERE file_id IS NOT NULL", 'file_id'),
            $this->repo->queryStrings("SELECT thumb_id FROM " . $this->repo->table() . " WHERE thumb_id IS NOT NULL", 'thumb_id')
        );
    }

    public function allByEssayId(int $essay_id): array
    {
        $images = [];
        $this->runAtomic(function (ilDBInterface $db) use ($essay_id, &$images) {
            $images = $this->repo->queryAllBy(['essay_id' => $essay_id], ['page_no' => 'ASC']);
        });
        return $images;
    }

    public function replaceByEssayId(int $essay_id, array $images): array
    {
        $deleted = [];
        $this->runAtomic(function (ilDBInterface $db) use ($essay_id, $images, &$deleted) {
            $deleted = $this->repo->queryAllBy(['essay_id' => $essay_id], ['page_no' => 'ASC']);
            $this->repo->deleteAllBy(['essay_id' => $essay_id]);
            foreach ($images as $image) {
                $this->repo->replace($image);
            }
        });
        return $deleted;
    }

    public function deleteByEssayId(int $essay_id): array
    {
        $deleted = [];
        $this->runAtomic(function (ilDBInterface $db) use ($essay_id, &$deleted) {
            $deleted = $this->repo->queryAllBy(['essay_id' => $essay_id], ['page_no' => 'ASC']);
            $this->repo->deleteAllBy(['essay_id' => $essay_id]);
        });
        return $deleted;
    }

    private function runAtomic(callable $callable): void
    {
        $atom = $this->db->buildAtomQuery();
        $atom->addTableLock('xlas_et_essay_image');
        $atom->addTableLock('xlas_et_essay_image_seq');
        $atom->addQueryCallable($callable);
        $atom->run();
    }
}
