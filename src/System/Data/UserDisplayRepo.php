<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilUserUtil;

class UserDisplayRepo implements \Edutiek\AssessmentService\System\Data\UserDisplayRepo
{
    public function __construct(
        private readonly ilUserUtil $user_util
    ) {
    }

    public function getOne(int $id, ?string $back_link): UserDisplay
    {
        $result = $this->user_util::getNamePresentation(
            $id,
            true,
            true,
            (string) $back_link,
            false,
            false,
            false,
            true
        );

        return new UserDisplay(
            $id,
            $data['img'] ?? null,
            $data['link'] ?? null
        );

    }

    public function getSome(array $ids, ?string $back_link): array
    {
        $result = $this->user_util::getNamePresentation(
            $ids,
            true,
            true,
            (string) $back_link,
            false,
            false,
            false,
            true
        );

        $displays = [];
        foreach ($ids as $id) {
            $displays[$id] = new UserDisplay(
                $id,
                $data[$id]['img'] ?? null,
                $data[$id]['link'] ?? null
            );
        }

        return $displays;
    }
}