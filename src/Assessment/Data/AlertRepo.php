<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\Alert;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ilDBInterface;
use ilDBConstants;

readonly class AlertRepo implements \Edutiek\AssessmentService\Assessment\Data\AlertRepo
{
    public function __construct(
        private RepositoryInterface $repo
    ) {
    }

    public function new(): Alert
    {
        return $this->repo->new();
    }

    public function one(int $id): ?Alert
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id], ['shown_from' => 'asc']);
    }
    public function allByAssIdAndWriterId(int $ass_id, int $writer_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id, 'writer_id' => $writer_id], ['shown_from' => 'asc']);
    }

    public function create(Alert $entity): void
    {
        $this->repo->insert($entity);
    }

    public function delete($id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }

    public function deleteByAssId(int $ass_id): void
    {
        $this->repo->deleteAllBy(['ass_id' => $ass_id]);
    }
}
