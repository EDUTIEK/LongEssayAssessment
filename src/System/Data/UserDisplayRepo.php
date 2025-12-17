<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilUserUtil;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\HydrationInterface;

class UserDisplayRepo implements \Edutiek\AssessmentService\System\Data\UserDisplayRepo, HydrationInterface
{
    /**
     * @var UserDisplay
     */
    private array $dehydrated = [];

    public function __construct(
        private readonly ilUserUtil $user_util
    ) {
    }

    public function one(int $id, ?string $back_link): UserDisplay
    {
        $data = $this->user_util::getNamePresentation(
            $id,
            true,
            true,
            (string) $back_link,
            false,
            false,
            false,
            true
        );

        return (new UserDisplay(
            $id
        ))->setValues(
            $data['img'] ?? null,
            $data['link'] ?? null
        );

    }

    public function some(array $ids, ?string $back_link): array
    {
        $data = $this->user_util::getNamePresentation(
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
            $displays[$id] = (new UserDisplay(
                $id
            ))->setValues(
                $data['img'] ?? null,
                $data['link'] ?? null
            );
        }

        return $displays;
    }

    public function dehydratedInstance(mixed $key_value): ?object
    {
        if ($key_value === null) {
            return null;
        }

        return $this->dehydrated[$key_value] ??= new UserDisplay($key_value);
    }

    public function hydrate(): void
    {
        $data = $this->user_util::getNamePresentation(
            array_keys($this->dehydrated),
            true,
            true,
            "",
            false,
            false,
            false,
            true
        );

        foreach ($data as $id => $row) {
            ($this->dehydrated[$id] ?? null)?->setValues(
                $row['img'] ?? null,
                $row['link'] ?? null
            );
            unset($this->dehydrated[$id]);
        }
    }
}
