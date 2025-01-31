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
        private RepositoryInterface $repo,
        private ilDBInterface $db
    ) {
    }

    public function new(): LogEntry
    {
        return $this->repo->new();
    }

    public function one(int $id): ?LogEntry
    {
        return $this->repo->queryOne("SELECT * FROM xlas_as_log_entry WHERE id = "
            . $this->db->quote($id, ilDBConstants::T_INTEGER));
    }

    public function allByAssId(int $ass_id): array
    {
        return $this->repo->queryAll("SELECT * FROM xlas_as_log_entry WHERE ass_id = "
            . $this->db->quote($ass_id, ilDBConstants::T_INTEGER));
    }

    public function create(LogEntry $alert): void
    {
        $this->repo->insert($alert);
    }

    public function delete($id): void
    {
        $this->db->manipulate("DELETE FROM xlas_as_log_entry WHERE id ="
            . $this->db->quote($id, ilDBConstants::T_INTEGER));
    }

    public function deleteByAssId(int $ass_id): void
    {
        $this->db->manipulate("DELETE FROM xlas_as_log_entry WHERE ass_id ="
            . $this->db->quote($ass_id, ilDBConstants::T_INTEGER));
    }
}
