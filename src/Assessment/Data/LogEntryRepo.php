<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\LogEntry;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use ilDBInterface;
use ilDBConstants;

readonly class LogEntryRepo implements \Edutiek\AssessmentService\Assessment\Data\LogEntryRepo
{
    public function __construct(
        private RepositoryInterface $repo
    ) {
    }

    public function new(): LogEntry
    {
        return $this->repo->new();
    }

    public function one(int $id): ?LogEntry
    {
        return $this->repo->queryOneBy(['id' => $id]);
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAllBy(['ass_id' => $ass_id]);
    }

    public function create(LogEntry $entity): void
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
