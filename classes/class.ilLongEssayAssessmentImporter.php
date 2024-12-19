<?php

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\DataService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\EditorSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\PdfSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\GradeLevel;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\RatingCriterion;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Location;

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
    private DataService $data_service;

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

        $object = new ilObjLongEssayAssessment();
        $object->setTitle((string) $xml->Title . " " . $this->plugin->txt('imported'));
        $object->setDescription((string) $xml->Description);
        $object->setImportId($a_id);
        $object->create();

        $new_id = $object->getId();
        $data_service = $this->localDI->getDataService($new_id);

        foreach ($xml->children() as $name => $element) {

            switch ($element->getName()) {
                case 'ObjectSettings':
                    $model = ObjectSettings::from($row = $this->getRowFromXml($element, ObjectSettings::model()));
                    $this->object_repo->save($model->setObjId($new_id));
                    break;
                case 'TaskSettings':
                    $model = TaskSettings::from($row = $this->getRowFromXml($element, TaskSettings::model()));
                    $this->object_repo->save($model->setTaskId($new_id)
                        ->setDescription($data_service->cleanupRichText($row['Description'] ?? null))
                        ->setClosingMessage($data_service->cleanupRichText($row['ClosingMessage'] ?? null))
                        ->setInstructions($data_service->cleanupRichText($row['Instructions'] ?? null))
                        ->setSolution($data_service->cleanupRichText($row['Solution'] ?? null))
                    );
                    break;
                case 'EditorSettings':
                    $model = EditorSettings::from($row = $this->getRowFromXml($element, EditorSettings::model()));
                    $this->task_repo->save($model->setTaskId($new_id));
                    break;
                case 'CorrectionSettings':
                    $model = CorrectionSettings::from($row = $this->getRowFromXml($element, CorrectionSettings::model()));
                    $this->task_repo->save($model->setTaskId($new_id));
                    break;
                case 'PdfSettings':
                    $model = PdfSettings::from($row = $this->getRowFromXml($element, PdfSettings::model()));
                    $this->task_repo->save($model->setTaskId($new_id));
                    break;
                case 'GradeLevel':
                    $model = GradeLevel::from($row = $this->getRowFromXml($element, GradeLevel::model()));
                    $this->object_repo->save($model->setObjectId($new_id)->setId(0));
                    break;
                case 'RatingCriterion':
                    $model = RatingCriterion::from($row = $this->getRowFromXml($element, RatingCriterion::model()));
                    $this->object_repo->save($model->setObjectId($new_id)->setId(0));
                    break;
                case 'Location':
                    $model = Location::from($row = $this->getRowFromXml($element, Location::model()));
                    $this->task_repo->save($model->setTaskId($new_id)->setId(0));
                    break;
            }
        }

        $a_mapping->addMapping('Plugins/xlas', 'xlas', $a_id, $new_id);
    }

    /**
     * Get a row array from the xml representation
     * All raw data is added with the original XML element name (PascalCase) to the row
     * Elements mapping to model properties (snake_case) and added with property names and mapped types
     *
     * @see ilLongEssayAssessmentExporter::addModelXml()
     */
    protected function getRowFromXml(SimpleXMLElement $element, Object $model): array
    {
        $row = [];

        // raw data with PascalCase names, probably needed for additional conversions
        foreach ($element->children() as $name => $value) {
            $value = (string) $value;
            $row[$name] = empty($value) ? null : $value;
        }

        // map to object properties with snake_case, do type conversions
        $reflect = new ReflectionClass($model);
        foreach ($reflect->getProperties() as $property) {
            $name = str_replace('_', '', ucwords($property->getName(), '_'));
            $value = $row[$name] ?? null;

            if ($value === null && $property->getType()->allowsNull()) {
                $row[$property->getName()] = null;
            } else {
                switch ((string) $property->getType()) {
                    case 'int':
                    case '?int':
                        $row[$property->getName()] = (int) $value;
                        break;
                    case 'float':
                    case '?float':
                        $row[$property->getName()] = (float) $value;
                        break;
                    case 'bool':
                    case '?bool':
                        $row[$property->getName()] = (bool) $value;
                        break;
                    case 'string':
                    case '?string':
                        $row[$property->getName()] = (string) $value;
                        break;
                }
            }
        }

        return $row;
    }
}
