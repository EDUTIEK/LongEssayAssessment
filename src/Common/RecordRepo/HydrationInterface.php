<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo;

/**
 * @template A of object
 */
interface HydrationInterface
{
    /**
     * Returns an dehydrated object of the type A with the key_value for the primary key.
     * @param mixed $key_value
     * @return A|null
     */
    public function dehydratedInstance(mixed $key_value): ?object;

    /**
     * Populates the dehydrated objects from $this->dehydratedInstance with data from the database.
     * This operation does not return any value.
     * @return void
     */
    public function hydrate(): void;
}