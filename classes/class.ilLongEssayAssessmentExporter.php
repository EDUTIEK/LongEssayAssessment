<?php

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\Resource;
use ILIAS\Filesystem\Util\LegacyPathHelper;
use ILIAS\DI\LoggingServices;
use ILIAS\ResourceStorage\Services as ResourceStorage;

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

class ilLongEssayAssessmentExporter extends ilXmlExporter
{
    private ResourceStorage $resource;
    private ilComponentLogger $logger;

    private ObjectRepository $object_repo;
    private TaskRepository $task_repo;

    private ilObjLongEssayAssessment $object;

    public function __construct()
    {
        global $DIC;
        $this->resource = $DIC->resourceStorage();
        $this->logger = $DIC->logger()->xlas();

        $local_di = LongEssayAssessmentDI::getInstance();

        $this->object_repo = $local_di->getObjectRepo();
        $this->task_repo = $local_di->getTaskRepo();
    }

    public function getXmlExportTailDependencies(
        string $a_entity,
        string $a_target_release,
        array $a_ids
    ): array {
        $deps = [];
        // service settings
        $deps[] = [
            "component" => "Services/Object",
            "entity" => "common",
            "ids" => $a_ids
        ];

        return $deps;
    }

    public function getXmlRepresentation(string $a_entity, string $a_schema_version, string $a_id): string
    {
        if (ilObject::_lookupType((int) $a_id) !== 'xlas') {
            return '';
        }

        $export_fs = LegacyPathHelper::deriveFilesystemFrom($this->getAbsoluteExportDirectory());
        $export_path = LegacyPathHelper::createRelativePath($this->getAbsoluteExportDirectory());
        $files_path = $export_path . '/Files';
        $export_fs->createDir($files_path);

        $ref_ids = ilObject::_getAllReferences($a_id);
        $ref_id = array_shift($ref_ids);
        $this->object = new ilObjLongEssayAssessment($ref_id);

        $writer = new ilXmlWriter();
        $writer->xmlStartTag("LongEssayAssessment");

        // basic object data
        $writer->xmlElement("Title", null, $this->object->getTitle());
        $writer->xmlElement("Description", null, $this->object->getDescription());

        $this->addModelXml($writer, $this->object_repo->getObjectSettingsById($a_id));
        $this->addModelXml($writer, $this->task_repo->getTaskSettingsById($a_id));
        $this->addModelXml($writer, $this->task_repo->getEditorSettingsById($a_id));
        $this->addModelXml($writer, $this->task_repo->getCorrectionSettingsById($a_id));
        $this->addModelXml($writer, $this->task_repo->getPdfSettingsById($a_id));
        foreach ($this->object_repo->getGradeLevelsByObjectId($a_id) as $model) {
            $this->addModelXml($writer, $model);
        }
        foreach ($this->object_repo->getRatingCriteriaByObjectId($a_id) as $model) {
            $this->addModelXml($writer, $model);
        }
        foreach ($this->task_repo->getLocationsByTaskId($a_id) as $model) {
            $this->addModelXml($writer, $model);
        }
        foreach ($this->task_repo->getResourceByTaskId($a_id) as $model) {

            /** @var Resource $model */
            $file_id = $model->getFileId();
            $file_name = '';
            if (!empty($file_id)) {
                try {
                    $identification = $this->resource->manage()->find($file_id);
                    $resource = $this->resource->manage()->getResource($identification);
                    $file_name = $resource->getCurrentRevision()->getTitle();
                    $export_fs->writeStream(
                        $files_path . '/' . $file_id,
                        $this->resource->consume()->stream($identification)->getStream()
                    );
                } catch (Exception $e) {
                    $this->logger->error(sprintf('LongEssayAssessment: EXPORT (ref_id %s): ', $ref_id)
                        . $e->getMessage());
                    $file_id = '';
                    $file_name = '';
                }
            }

            /** @noinspection PhpParamsInspection */
            $row = $this->getModelRowForXml($model);
            $row['FileId'] = $file_id;
            $row['FileName'] = $file_name;
            $this->addRowXml($writer, 'Resource', $row);
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
     * Add the xml of a RecordData model to the writer
     */
    private function addModelXml(ilXmlWriter $writer, RecordData $model): void
    {
        $reflect = new ReflectionClass($model);
        $this->addRowXml($writer, $reflect->getShortName(), $this->getModelRowForXml($model));
    }

    /**
     * Add the xml of a row to the writer
     */
    private function addRowXml(ilXmlWriter $writer, string $tag, array $row): void
    {
        $writer->xmlStartTag($tag);
        foreach ($row as $name => $value) {
            $writer->xmlElement($name, null, (string) $value, true, true);
        }
        $writer->xmlEndTag($tag);
    }

    /**
     * Get a model row with keys renamed for Xml
     */
    private function getModelRowForXml(RecordData $model): array
    {
        $row = [];
        foreach ($model->row() as $key => $value) {
            $name = str_replace('_', '', ucwords($key, '_'));
            $row[$name] = $value;
        }
        return $row;
    }
}
