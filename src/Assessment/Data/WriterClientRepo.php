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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;

use Edutiek\AssessmentService\Assessment\Data\WriterClient;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;

readonly class WriterClientRepo implements \Edutiek\AssessmentService\Assessment\Data\WriterClientRepo
{
    public function __construct(private RepositoryInterface $repo)
    {
    }

    public function new(): WriterClient
    {
        return $this->repo->new();
    }

    public function oneByWriterIdAndSessionId(int $writer_id, string $session_id): ?WriterClient
    {
        return $this->repo->queryOneBy(['writer_id' => $writer_id, 'session_id' => $session_id]);
    }

    public function oneByWriterIdAndTokenId(int $writer_id, int $token_id): ?WriterClient
    {
        return $this->repo->queryOneBy(['writer_id' => $writer_id, 'token_id' => $token_id]);
    }

    public function allByWriterId(int $writer_id): array
    {
        return $this->repo->queryAllBy(['writer_id' => $writer_id], ['last_access' => 'asc']);
    }


    public function save(WriterClient $client): void
    {
        $this->repo->replace($client);
    }

    public function deleteByWriterIdAndTokenId(int $writer_id, int $token_id): void
    {
        $this->repo->deleteAllBy(['writer_id' => $writer_id, 'token_id' => $token_id]);
    }

    public function deleteByWriterId(int $writer_id): void
    {
        $this->repo->deleteAllBy(['writer_id' => $writer_id]);
    }

}
