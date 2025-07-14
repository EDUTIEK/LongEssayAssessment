<?php

use ILIAS\Plugin\LongEssayAssessment\InheritParentPlugin;

class ilLongEssayAssessmentCollectionPlugin extends ilUserInterfaceHookPlugin
{
    use InheritParentPlugin;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        parent::__construct($db, $component_repository, $id);

    }

    function getPluginName(): string
    {
        return "LongEssayAssessmentCollection";
    }
}