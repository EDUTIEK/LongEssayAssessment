<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;
use ILIAS\Refinery;
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;

trait ConfirmationIds
{
    protected RequestVariables $post;
    protected Refinery\Factory $refinery;

    /**
     * @return int[]
     */
    protected function confirmationIds() : array
    {
        if($this->http->wrapper()->post()->has("interruptive_items")) {
            return $this->http->wrapper()->post()->retrieve(
                "interruptive_items",
                $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->int())
            );
        }
        return [];
    }
}