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

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\RepositoryInterface;
use Edutiek\AssessmentService\System\Data\Token;
use DateTimeImmutable;

readonly class TokenRepo implements \Edutiek\AssessmentService\System\Data\TokenRepo
{
    public function __construct(private RepositoryInterface $repo)
    {
    }

    public function new(int $user_id): Token
    {
        /** @var Token $token */
        $token = $this->repo->new()
            ->setUserId($user_id)
            ->setToken(bin2hex(random_bytes(16)))
            ->setNewValidity();

        $this->repo->replace($token);

        return $token;
    }

    public function allByUserId(int $user_id): array
    {
        return $this->repo->queryAllBy(['user_id' => $user_id]);
    }

    public function oneByToken(string $token): ?Token
    {
        return $this->repo->queryOneBy(['token' => $token]);
    }

    public function save(Token $entity): void
    {
        $this->repo->replace($entity);
    }

    public function delete(int $id): void
    {
        $this->repo->deleteAllBy(['id' => $id]);
    }


    public function deleteByUserId(int $user_id): void
    {
        $this->repo->deleteAllBy(['user_id' => $user_id]);
    }
}
