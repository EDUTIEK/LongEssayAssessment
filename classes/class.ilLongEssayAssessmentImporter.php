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

use Edutiek\AssessmentService\Assessment\Data\DisabledGroup;
use ILIAS\Filesystem\Filesystem;
use ILIAS\Filesystem\Util\LegacyPathHelper;
use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;
use Edutiek\AssessmentService\System\Entity\KeyCase;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\PdfSettings;
use Edutiek\AssessmentService\Assessment\Data\Location as Location;
use Edutiek\AssessmentService\Assessment\Data\GradeLevel as GradeLevel;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskType;
use Edutiek\AssessmentService\Task\Data\Settings as TaskSettings;
use Edutiek\AssessmentService\Task\Data\CorrectionSettings as TaskCorrectionSettings;
use Edutiek\AssessmentService\Task\Data\RatingCriterion as TaskRatingCriterion;
use Edutiek\AssessmentService\EssayTask\Data\WritingSettings as EssayWritingSettings;
use Edutiek\AssessmentService\EssayTask\Data\TaskSettings as EssayTaskSettings;
use Edutiek\AssessmentService\Task\Data\Resource;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo as FileInfoModel;

class ilLongEssayAssessmentImporter extends ilXmlImporter
{
    private const FilesPath = 'XlasFiles';

    private ilObjUser $user;

    private ilLongEssayAssessmentPlugin $plugin;
    private ilObjLongEssayAssessment $object;
    private SystemApi $system_api;
    private AssessmentApi $assessment_api;
    private EssayTaskApi $essay_task_api;
    private TaskApi $task_api;

    private Filesystem $import_fs;
    private string $files_path;
    private ilFileServicesPolicy $file_policy;

    public function __construct()
    {
        global $DIC;

        $this->user = $DIC->user();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->file_policy = new ilFileServicesPolicy($DIC->fileServiceSettings());
    }

    /**
     * Initialisations after import directory is set
     */
    public function init(): void
    {
        $directory = $this->getImportDirectory();
        $import_path = LegacyPathHelper::createRelativePath($this->getImportDirectory());
        $this->import_fs = LegacyPathHelper::deriveFilesystemFrom($this->getImportDirectory());
        $this->files_path = $import_path . '/' . self::FilesPath;
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

        $ass_id = $this->initObject(
            $a_id,
            $xml->Title . ' ' . $this->plugin->txt('imported'),
            (string) $xml->Description
        );

        /** @var array<int, int> $task_match */
        $task_id_match = [];

        foreach ($xml->children() as $name => $element) {

            switch ($element->getName()) {

                case 'AssessmentOrgaSettings':
                    $entity = $this->assessment_api->orgaSettings()->get();
                    $this->applyRow($row = $this->getRow($element), $entity, OrgaSettings::class);
                    $this->assessment_api->orgaSettings()->save($entity->setAssId($ass_id));
                    break;

                case 'AssessmentPdfSettings':
                    $entity = $this->assessment_api->pdfSettings()->get();
                    $this->applyRow($row = $this->getRow($element), $entity, PdfSettings::class);
                    $this->assessment_api->pdfSettings()->save($entity->setAssId($ass_id));
                    break;

                case 'AssessmentCorrectionSettings':
                    $entity = $this->assessment_api->correctionSettings()->get();
                    $this->applyRow($row = $this->getRow($element), $entity, CorrectionSettings::class);
                    $this->assessment_api->correctionSettings()->save($entity->setAssId($ass_id));
                    break;

                case 'AssessmentLocation':
                    $entity = $this->assessment_api->location()->new();
                    $this->applyRow($row = $this->getRow($element), $entity, Location::class);
                    $this->assessment_api->location()->save($entity->setAssId($ass_id)->setId(0));
                    break;

                case 'AssessmentGradeLevel':
                    $entity = $this->assessment_api->gradLevel()->new();
                    $this->applyRow($row = $this->getRow($element), $entity, GradeLevel::class);
                    $this->assessment_api->gradLevel()->save($entity->setAssId($ass_id)->setId(0));
                    break;

                case 'AssessmentDisabledGroup':
                    $entity = $this->assessment_api->disabledGroup()->new();
                    $this->applyRow($row = $this->getRow($element), $entity, DisabledGroup::class);
                    $this->assessment_api->disabledGroup()->save($entity->setAssId($ass_id));
                    break;

                case 'TaskCorrectionSettings':
                    $entity = $this->task_api->correctionSettings()->get();
                    $this->applyRow($row = $this->getRow($element), $entity, TaskCorrectionSettings::class);
                    $this->task_api->correctionSettings()->save($entity->setAssId($ass_id));
                    break;

                case 'EssayTaskWritingSettings':
                    $entity = $this->essay_task_api->writingSettings()->get();
                    $this->applyRow($row = $this->getRow($element), $entity, EssayWritingSettings::class);
                    $this->essay_task_api->writingSettings()->save($entity->setAssId($ass_id));
                    break;

                case 'TaskSettings':
                    $task_id = empty($task_id_match) ? $this->task_api->manager()->first()->getId() : null;
                    $task_id ??= $this->task_api->manager()->create(new TaskInfo('', TaskType::ESSAY));
                    $entity = $this->task_api->settings($task_id)->get();
                    $this->applyRow($row = $this->getRow($element), $entity, TaskSettings::class);
                    $task_id_match[$entity->getTaskId()] = $task_id;
                    $this->task_api->settings($task_id)->save($entity->setAssId($ass_id)->setTaskId($task_id));
                    break;

                case 'EssayTaskSettings':
                    $entity = $this->essay_task_api->taskSettings(0)->get();
                    $this->applyRow($row = $this->getRow($element), $entity, EssayTaskSettings::class);
                    $task_id = $task_id_match[$entity->getTaskId()];
                    $this->essay_task_api->taskSettings($task_id)->save($entity->setAssId($ass_id)->setTaskId($task_id));
                    break;

                case 'TaskRatingCriterion':
                    $entity = $this->task_api->ratingCriterion(0)->new();
                    $this->applyRow($row = $this->getRow($element), $entity, TaskRatingCriterion::class);
                    $task_id = $task_id_match[$entity->getTaskId()];
                    $this->task_api->ratingCriterion($task_id)->save($entity->setTaskId($task_id)->setCorrectorId(null));
                    break;

                case 'TaskResource':
                    $entity = $this->task_api->resource(0)->new();
                    $this->applyRow($row = $this->getRow($element), $entity, Resource::class);
                    $task_id = $task_id_match[$entity->getTaskId()];
                    $file_id = $row['FileId'] ?? null;
                    $file_name = $row['FileName'] ?? null;
                    if ($file_id !== null) {
                        $file_id = $this->addFile($file_id, $file_name);
                    }
                    $this->task_api->resource($task_id)->save($entity->setTaskId($task_id)->setFileId($file_id));
            }
        }

        $a_mapping->addMapping('Plugins/xlas', 'xlas', $a_id, (string) $ass_id);
    }

    /**
     * Apply an imported array of scalar values to an entity
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
     * Only scalar types and null are supported
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
                case 'double':
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

    /**
     * Add a file and return its id as string
     */
    public function addFile(string $export_file_id, ?string $export_file_name): ?string
    {
        $export_file_id = $this->file_policy->ascii($export_file_id);
        if ($export_file_name !== null) {
            $export_file_name = $this->file_policy->prepareFileNameForConsumer($export_file_name);
        }

        $stream = $this->import_fs->readStream($this->files_path . '/' . $export_file_id);
        $info = (new FileInfoModel())->setId(null)->setFileName($export_file_name);
        return $this->system_api->fileStorage()->saveFile($stream->detach(), $info)->getId();
    }
}
