<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

use ilDBInterface;

readonly class Factory
{
    public function __construct(
        private ilDBInterface $db,
        private Generate $g
    ) {
    }

    public function repository(string $classname) {
        return new CacheRepository(new DatabaseRepository($this->db, $this->g->readModel($classname)));
    }
}