<?php

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;

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

class ilLongEssayAssessmentImporter extends ilXmlImporter
{
    private LongEssayAssessmentDI $localDI;
    private ilLongEssayAssessmentPlugin $plugin;

    private ObjectRepository $object_repo;
    private TaskRepository $task_repo;

    private ilObjLongEssayAssessment $object;


    public function __construct()
    {
        $this->localDI = LongEssayAssessmentDI::getInstance();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();

        $object_repo = $this->localDI->getObjectRepo();
        $task_repo = $this->localDI->getTaskRepo();
    }

    public function importXmlRepresentation(
        string $a_entity,
        string $a_id,
        string $a_xml,
        ilImportMapping $a_mapping
    ) : void {

        $this->object = new ilObjLongEssayAssessment();
        $this->object->create(true);

        $xml = simplexml_load_string($a_xml);

        $object = new ilObjLongEssayAssessment();
        $object->setTitle((string) $xml->title . " " . $this->plugin->txt('copy'));
        $object->setDescription((string) $xml->description);
        $object->setImportId($a_id);
        $object->create();

        $new_id = $object->getId();
        $a_mapping->addMapping("Plugins/xlas", "xlas", $a_id, $new_id);
    }
}