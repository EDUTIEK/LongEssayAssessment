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

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Example;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\DatabaseRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

class DummyRepository
{
    /**
     * @var RepositoryInterface<Dummy> $repo
     */
    private readonly RepositoryInterface $repo;

    public function __construct(ilDBInterface $db, Generate $g)
    {
        // Use $g->readModelFromClass(Dummy::class) if you don't want / can write to a file.
        $this->repo = new CacheRepository(new DatabaseRepository($db, $g->readModel(Dummy::class)));
    }

    public function insert(Dummy $dummy): void
    {
        $this->repo->insert($dummy);
    }

    public function update(Dummy $dummy): void
    {
        $this->repo->update($dummy);
    }

    public function replace(Dummy $dummy): void
    {
        $this->repo->replace($dummy);
    }

    public function delete(Dummy $dummy): void
    {
        $this->repo->delete($dummy);
    }

    public function all(): array
    {
        return $this->repo->all();
    }

    public function findById(int $id): ?Dummy
    {
        return $this->repo->queryOne('select * from ' . $this->repo->table() . ' where hej = ' . $id);
    }
}
