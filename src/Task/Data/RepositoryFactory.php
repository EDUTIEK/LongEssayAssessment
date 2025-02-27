<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Task\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryFactory as FactoryTrait;

class RepositoryFactory implements \Edutiek\AssessmentService\Task\Data\Repositories
{
    use FactoryTrait;

    public function __construct(
        Generate $g,
        ilDBInterface $db,
    ) {
        $this->g = $g;
        $this->db = $db;
    }

    public function correctorAssignment(): CorrectorAssignmentRepo
    {
        return $this->repo(CorrectorAssignmentRepo::class, CorrectorAssignment::class);
    }

    public function resource(): ResourceRepo
    {
        return $this->repo(ResourceRepo::class, Resource::class);
    }

    public function settings(): SettingsRepo
    {
        return $this->repo(SettingsRepo::class, Settings::class);
    }

    public function writerComment(): WriterCommentRepo
    {
        return $this->repo(WriterCommentRepo::class, WriterComment::class);
    }
}
