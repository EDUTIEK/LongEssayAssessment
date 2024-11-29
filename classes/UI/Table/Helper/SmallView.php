<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Helper;

trait SmallView
{
    protected function smallView(?array $additional_parameters) : bool
    {
        return isset($additional_parameters["small_view"]) ? (bool)$additional_parameters["small_view"] : false;
    }

    protected function setSmallView(?array $additional_parameters = null) : array
    {
        $additional_parameters = $additional_parameters ?? [];
        $additional_parameters["small_view"] = true;
        return $additional_parameters;
    }
}