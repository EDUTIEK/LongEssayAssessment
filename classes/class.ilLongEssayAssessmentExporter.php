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

use ILIAS\Filesystem\Util\LegacyPathHelper;
use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;
use Edutiek\AssessmentService\System\Entity\KeyCase;
use Edutiek\AssessmentService\Assessment\Data\CorrectionSettings;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\PdfSettings;
use Edutiek\AssessmentService\Assessment\Data\Location;
use Edutiek\AssessmentService\Assessment\Data\GradeLevel;
use Edutiek\AssessmentService\Assessment\Data\DisabledGroup;
use Edutiek\AssessmentService\Task\Data\Settings as TaskSettings;
use Edutiek\AssessmentService\Task\Data\CorrectionSettings as TaskCorrectionSettings;
use Edutiek\AssessmentService\Task\Data\RatingCriterion as TaskRatingCriterion;
use Edutiek\AssessmentService\EssayTask\Data\TaskSettings as EssayTaskSettings;
use Edutiek\AssessmentService\EssayTask\Data\WritingSettings as EssayWritingSettings;
use ILIAS\Filesystem\Stream\Streams;
use Edutiek\AssessmentService\Task\Data\Resource;

class ilLongEssayAssessmentExporter extends ilXmlExporter
{
    private const FilesPath = 'XlasFiles';

    private ilObjUser $user;

    private ilLongEssayAssessmentPlugin $plugin;
    private ilObjLongEssayAssessment $object;
    private SystemApi $system_api;
    private AssessmentApi $assessment_api;
    private EssayTaskApi $essay_task_api;
    private TaskApi $task_api;

    public function __construct()
    {
        global $DIC;

        $this->user = $DIC->user();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
    }

    public function initObject(int $obj_id): void
    {
        $ref_ids = ilObject::_getAllReferences($obj_id);
        $ref_id = array_shift($ref_ids);

        $this->object = new ilObjLongEssayAssessment($ref_id);

        $this->system_api = $this->plugin->dic()->system();
        $this->assessment_api = $this->plugin->dic()->assessment($this->object->getAssId(), $this->user->getId());
        $this->task_api = $this->plugin->dic()->task($this->object->getAssId(), $this->user->getId());
        $this->essay_task_api = $this->plugin->dic()->essayTask($this->object->getAssId(), $this->user->getId());
    }

    public function getXmlExportTailDependencies(
        string $a_entity,
        string $a_target_release,
        array $a_ids
    ): array {
        $deps = [];
        // service settings
        $deps[] = [
            "component" => "components/ILIAS/Object",
            "entity" => "common",
            "ids" => $a_ids
        ];

        return $deps;
    }

    /**
     * Get the xml string with all entity data of the assessment definition
     *
     * Important:
     * entities are added in sequence of mutual dependency
     * They will be imported in the same sequence and referenced ids will be mapped
     * @see ilLongEssayAssessmentImporter::importXmlRepresentation()
     */
    public function getXmlRepresentation(string $a_entity, string $a_schema_version, string $a_id): string
    {
        if (ilObject::_lookupType((int) $a_id) !== ilLongEssayAssessmentPlugin::ID) {
            return '';
        }

        $this->initObject((int) $a_id);

        $export_fs = LegacyPathHelper::deriveFilesystemFrom($this->getAbsoluteExportDirectory());
        $export_path = LegacyPathHelper::createRelativePath($this->getAbsoluteExportDirectory());
        $files_path = $export_path . '/' . self::FilesPath;
        $export_fs->createDir($files_path);

        $writer = new ilXmlWriter();
        $writer->xmlStartTag("LongEssayAssessment");

        // basic object data
        $writer->xmlElement("Title", null, $this->object->getTitle());
        $writer->xmlElement("Description", null, $this->object->getDescription());

        $this->addEntityXml(
            $writer,
            'AssessmentOrgaSettings',
            $this->assessment_api->orgaSettings()->get(),
            OrgaSettings::class
        );

        $this->addEntityXml(
            $writer,
            'AssessmentPdfSettings',
            $this->assessment_api->pdfSettings()->get(),
            PdfSettings::class
        );

        $this->addEntityXml(
            $writer,
            'AssessmentCorrectionSettings',
            $this->assessment_api->correctionSettings()->get(),
            CorrectionSettings::class
        );

        foreach ($this->assessment_api->location()->all() as $location) {
            $this->addEntityXml($writer, 'AssessmentLocation', $location, Location::class);
        }

        foreach ($this->assessment_api->gradeLevel()->all() as $level) {
            $this->addEntityXml($writer, 'AssessmentGradeLevel', $level, GradeLevel::class);
        }

        foreach ($this->assessment_api->disabledGroup()->all() as $group) {
            $this->addEntityXml($writer, 'AssessmentDisabledGroup', $group, DisabledGroup::class);
        }

        $this->addEntityXml(
            $writer,
            'TaskCorrectionSettings',
            $this->task_api->correctionSettings()->get(),
            TaskCorrectionSettings::class
        );

        $this->addEntityXml(
            $writer,
            'EssayTaskWritingSettings',
            $this->essay_task_api->writingSettings()->get(),
            EssayWritingSettings::class
        );

        foreach ($this->task_api->manager()->all() as $task_info) {

            $this->addEntityXml(
                $writer,
                'TaskSettings',
                $this->task_api->settings($task_info->getId())->get(),
                TaskSettings::class
            );

            foreach ($this->task_api->ratingCriterion($task_info->getId())->allByCorrectorId(null) as $criterion) {
                $this->addEntityXml($writer, 'TaskRatingCriterion', $criterion, TaskRatingCriterion::class);
            }

            foreach ($this->task_api->resource($task_info->getId())->all() as $resource) {
                $file_id = $resource->getFileId();
                $file_name = '';
                if (!empty($file_id)) {
                    $file_name = $this->system_api->fileStorage()->getFileInfo($file_id)->getFileName() ?? '';
                    $export_fs->writeStream(
                        $files_path . '/' . $file_id,
                        Streams::ofResource($this->system_api->fileStorage()->getFileStream($resource->getFileId()))
                    );
                }
                $row = $this->system_api->entity()->toPrimitives($resource, Resource::class, KeyCase::PASCAL_CASE);
                $row['FileId'] = $file_id;
                $row['FileName'] = $file_name;
                $this->addRowXml($writer, 'TaskResource', $row);
            }
        }

        $writer->xmlEndTag("LongEssayAssessment");

        return $writer->xmlDumpMem(false);
    }

    public function init(): void
    {
        // no initialisation needed at the moment
    }

    public function getValidSchemaVersions(string $a_entity): array
    {
        return array(
            "5.2.0" => array(
                "namespace" => "http://www.ilias.de/Plugins/LongEssayAssessment/md/5_2",
                "xsd_file" => "ilias_md_5_2.xsd",
                "min" => "5.2.0",
                "max" => ""
            )
        );
    }

    /**
     * Add the xml of an assessment service entity to the writer
     */
    private function addEntityXml(ilXmlWriter $writer, string $tag, object $entity, string $class): void
    {
        $row = $this->system_api->entity()->toPrimitives($entity, $class, KeyCase::PASCAL_CASE);
        $this->addRowXml($writer, $tag, $row);
    }

    /**
     * Add the xml of a primitives data string values to the writer
     */
    private function addRowXml(ilXmlWriter $writer, string $tag, array $row): void
    {
        $writer->xmlStartTag($tag);
        foreach ($row as $name => $value) {
            $writer->xmlElement($name, ['type' => strtolower(gettype($value))], (string) $value, true, true);
        }
        $writer->xmlEndTag($tag);
    }
}
