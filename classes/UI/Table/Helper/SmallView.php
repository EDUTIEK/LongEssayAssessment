<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

trait SmallView
{
    protected function smallView(?array $additional_parameters) : bool
    {
        return isset($additional_parameters["small_view"]) ? (bool)$additional_parameters["small_view"] : false;
    }
}