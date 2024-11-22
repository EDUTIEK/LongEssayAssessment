<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table;

use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\Refinery;

trait ConfirmationIds
{
    protected ArrayBasedRequestWrapper $post;
    protected Refinery\Factory $refinery;

    /**
     * @return int[]
     */
    protected function confirmationIds() : array
    {
        if($this->post->has("interruptive_items")) {
            return $this->post->retrieve(
                "interruptive_items",
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int())
            );
        }
        return [];
    }
}