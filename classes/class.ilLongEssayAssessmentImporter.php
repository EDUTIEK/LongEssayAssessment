<?php

declare(strict_types=1);

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

use ILIAS\Filesystem\Filesystem;
use ILIAS\Filesystem\Util\LegacyPathHelper;
use ILIAS\DI\LoggingServices;

use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;
use Edutiek\AssessmentService\System\Entity\KeyCase;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;

class ilLongEssayAssessmentImporter extends ilXmlImporter
{
    private ilObjUser $user;
    private ilComponentLogger $logger;

    private ilLongEssayAssessmentPlugin $plugin;
    private ilObjLongEssayAssessment $object;
    private SystemApi $system_api;
    private AssessmentApi $assessment_api;
    private EssayTaskApi $essay_task_api;
    private TaskApi $task_api;

    private Filesystem $import_fs;
    private string $files_path;

    public function __construct()
    {
        global $DIC;

        $this->user = $DIC->user();
        $this->logger = $DIC->logger()->xlas();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
    }

    /**
     * Initialisations after import directory is set
     */
    public function init(): void
    {
        $import_path = LegacyPathHelper::createRelativePath($this->getImportDirectory());
        $this->import_fs = LegacyPathHelper::deriveFilesystemFrom($this->getImportDirectory());
        $this->files_path = $import_path . '/Plugins/xlas/set_1/expDir_1/Files';
    }

    /**
     * Initialisation for the imported object
     */
    public function initObject(string $import_id, string $title, string $description): int
    {
        $this->object = new ilObjLongEssayAssessment();
        $this->object->setImportId($import_id);
        $this->object->setTitle($title);
        $this->object->setDescription($description);
        $this->object->create();

        $this->system_api = $this->plugin->dic()->system();
        $this->assessment_api = $this->plugin->dic()->assessment($this->object->getId(), $this->user->getId());
        $this->task_api = $this->plugin->dic()->task($this->object->getId(), $this->user->getId());
        $this->essay_task_api = $this->plugin->dic()->essayTask($this->object->getId(), $this->user->getId());

        return $this->object->getId();
    }

    public function importXmlRepresentation(
        string $a_entity,
        string $a_id,
        string $a_xml,
        ilImportMapping $a_mapping
    ): void {
        $xml = new SimpleXMLElement($a_xml);

        $new_id = $this->initObject($a_id,
            $xml->Title . ' ' . $this->plugin->txt('imported'),
            (string) $xml->Description);

        foreach ($xml->children() as $name => $element) {

            switch ($element->getName()) {
                case 'OrgaSettings':
                    $entity =  $this->assessment_api->orgaSettings()->get();
                    $this->applyRow($row = $this->getRow($element), $entity, OrgaSettings::class);
                    $this->assessment_api->orgaSettings()->save($entity->setAssId($new_id));
                    break;
//                case 'TaskSettings':
//                    $model = TaskSettings::from($row = $this->getRowFromXml($element, TaskSettings::model()));
//                    $this->object_repo->save(
//                        $model->setTaskId($new_id)
//                        ->setDescription($data_service->cleanupRichText($row['Description'] ?? null))
//                        ->setClosingMessage($data_service->cleanupRichText($row['ClosingMessage'] ?? null))
//                        ->setInstructions($data_service->cleanupRichText($row['Instructions'] ?? null))
//                        ->setSolution($data_service->cleanupRichText($row['Solution'] ?? null))
//                    );
//                    break;
//                case 'EditorSettings':
//                    $model = EditorSettings::from($row = $this->getRowFromXml($element, EditorSettings::model()));
//                    $this->task_repo->save($model->setTaskId($new_id));
//                    break;
//                case 'CorrectionSettings':
//                    $model = CorrectionSettings::from($row = $this->getRowFromXml($element, CorrectionSettings::model()));
//                    $this->task_repo->save($model->setTaskId($new_id));
//                    break;
//                case 'PdfSettings':
//                    $model = PdfSettings::from($row = $this->getRowFromXml($element, PdfSettings::model()));
//                    $this->task_repo->save($model->setTaskId($new_id));
//                    break;
//                case 'GradeLevel':
//                    $model = GradeLevel::from($row = $this->getRowFromXml($element, GradeLevel::model()));
//                    $this->object_repo->save($model->setObjectId($new_id)->setId(0));
//                    break;
//                case 'RatingCriterion':
//                    $model = RatingCriterion::from($row = $this->getRowFromXml($element, RatingCriterion::model()));
//                    $this->object_repo->save($model->setObjectId($new_id)->setId(0));
//                    break;
//                case 'Location':
//                    $model = Location::from($row = $this->getRowFromXml($element, Location::model()));
//                    $this->task_repo->save($model->setTaskId($new_id)->setId(0));
//                    break;
//                case 'Resource':
//                    $model = Resource::from($row = $this->getRowFromXml($element, Resource::model()));
//                    $file_id = '';
//                    try {
//                        $file_id = $this->addResourceFile(
//                            ilUtil::secureString($row['FileId'] ?? ''),
//                            ilUtil::secureString($row['FileName'] ?? '')
//                        );
//                    } catch (Exception $e) {
//                        $this->logger->error(sprintf('LongEssayAssessment: IMPORT (obj_id %s): ', $new_id)
//                            . $e->getMessage());
//                    }
//                    $this->task_repo->save($model->setTaskId($new_id)->setFileId($file_id)->setId(0));
            }
        }

        $a_mapping->addMapping('Plugins/xlas', 'xlas', $a_id, (string) $new_id);
    }

    /**
     * Apply an imported row array to an entity
     */
    private function applyRow(array $row, object $entity, string $class)
    {
        $this->system_api->entity()->fromPrimitives($row, $entity, $class, KeyCase::PASCAL_CASE);
        $this->system_api->entity()->secure($entity, $class);
    }


    /**
     * Get a row array from the xml representation
     * Names of the sub elements are taken as keys
     * Contents of the sub elements are taken as values
     * The values are cast by the types given in the 'type' attributes
     * Only scalar types and null are supported see
     *
     * @see ilLongEssayAssessmentExporter::addRowXml()
     * @see gettype()
     */
    protected function getRow(SimpleXMLElement $element): array
    {
        $row = [];
        foreach ($element->children() as $name => $child) {
            $value = (string) $child;
            switch (strtolower((string) $child['type'])) {
                case 'null':
                    $row[$name] = null;
                    break;
                case 'boolean':
                    $row[$name] = (bool) $value;
                    break;
                case 'integer':
                    $row[$name] = (int) $value;
                    break;
                case'double':
                    $row[$name] = (float) $value;
                    break;
                case 'string':
                default:
                    $row[$name] = $value;
                    break;
            }
        }
        return $row;
    }
}
