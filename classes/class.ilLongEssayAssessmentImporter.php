<?php

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;

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

        $this->object_repo = $this->localDI->getObjectRepo();
        $this->task_repo = $this->localDI->getTaskRepo();
    }

    public function importXmlRepresentation(
        string $a_entity,
        string $a_id,
        string $a_xml,
        ilImportMapping $a_mapping
    ): void {

        $this->object = new ilObjLongEssayAssessment();
        $this->object->create(true);

        $xml = new SimpleXMLElement($a_xml);
        log_var($xml, 'xml');

        $object = new ilObjLongEssayAssessment();
        $object->setTitle((string) $xml->Title . " " . $this->plugin->txt('imported'));
        $object->setDescription((string) $xml->Description);
        $object->setImportId($a_id);
        $object->create();

        $new_id = $object->getId();

        foreach ($xml->children() as $name => $element) {

            switch ($element->getName()) {
                case "ObjectSettings":
                    $model = $this->getModelFromXml($element, ObjectSettings::model());
                    $model->setObjId($new_id);
                    $this->object_repo->save($model);
                    break;
                case "TaskSettings":
                    $model = $this->getModelFromXml($element, TaskSettings::model());
                    $model->setTaskId($new_id);
                    $this->object_repo->save($model);
                    break;
            }
        }

        $a_mapping->addMapping("Plugins/xlas", "xlas", $a_id, $new_id);
    }

    /**
     * Get a model from the xml representation
     * @see ilLongEssayAssessmentExporter::addModelXml()
     */
    protected function getModelFromXml(SimpleXMLElement $element, RecordData $model): RecordData
    {
        $map = [];
        foreach (array_keys($model->row()) as $key) {
            $name = str_replace('_', '', ucwords($key, '_'));
            $map[$name] = $key;
        }

        $row = [];
        foreach ($element->children() as $name => $value) {
            $key = $map[$name];
            $row[$key] = (string) $value;
        }

        return $model::from($row);
    }
}
