<?php

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

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment;

use ILIAS\DI\Container;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Data\ReferenceId;
use ILIAS\Export\ExportHandler\I\Info\File\CollectionInterface as ilExportHandlerFileInfoCollectionInterface;
use ILIAS\Export\ExportHandler\I\Consumer\File\Identifier\CollectionInterface as ilExportHandlerConsumerFileIdentifierCollectionInterface;
use ILIAS\Export\ExportHandler\I\Consumer\File\Identifier\HandlerInterface as ilExportHandlerConsumerFileIdentifierInterface;
use ILIAS\Export\ExportHandler\I\Consumer\Context\HandlerInterface as ilExportHandlerConsumerContextInterface;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\Assessment\Export\FullService as ExportService;
use Edutiek\AssessmentService\Assessment\Data\ExportType;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\ExportFile;
use ILIAS\ResourceStorage\Services as ResourceStorage;
use ilCtrlInterface;
use ilGlobalTemplateInterface;
use ilLanguage;

abstract class ExportOption extends \ILIAS\Export\ExportHandler\Consumer\ExportOption\BasicHandler
{
    private DataFactory $data_factory;
    private ilCtrlInterface $ctrl;
    private ilLongEssayAssessmentPlugin $plugin;
    private ExportService $export_service;
    private ResourceStorage $resource_storage;
    private int $user_id;
    private ilGlobalTemplateInterface $tpl;

    public function init(
        Container $DIC
    ): void {
        $this->data_factory = new DataFactory();
        $this->ctrl = $DIC->ctrl();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->resource_storage = $DIC->resourceStorage();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->user_id = $DIC->user()->getId();
    }

    abstract public function getServiceExportType(): ExportType;

    public function getExportOptionId(): string
    {
        return 'xlas' . $this->getServiceExportType()->value;
    }

    /**
     * Type shown in the export table
     */
    public function getExportType(): string
    {
        return $this->plugin->txt('export_type_' . $this->getServiceExportType()->value);
    }

    /**
     * Label in the Export dropdown
     */
    public function getLabel(): string
    {
        return $this->plugin->txt('export_type_' . $this->getServiceExportType()->value);
    }

    public function getSupportedRepositoryObjectTypes(): array
    {
        return [ilLongEssayAssessmentPlugin::ID];
    }

    public function onDeleteFiles(
        ilExportHandlerConsumerContextInterface $context,
        ilExportHandlerConsumerFileIdentifierCollectionInterface $file_identifiers
    ): void {
        $service = $this->service($context->exportObject()->getId(), $context->exportObject()->getRefId());

        foreach ($service->getFilesByIds($file_identifiers->toStringArray()) as $file) {
            $service->deleteFile($file);
        }
    }

    public function onDownloadFiles(
        ilExportHandlerConsumerContextInterface $context,
        ilExportHandlerConsumerFileIdentifierCollectionInterface $file_identifiers
    ): void {
        $service = $this->service($context->exportObject()->getId(), $context->exportObject()->getRefId());

        foreach ($service->getFilesByIds($file_identifiers->toStringArray()) as $file) {
            $service->downloadFile($file);
        }
    }

    public function onDownloadWithLink(
        ReferenceId $reference_id,
        ilExportHandlerConsumerFileIdentifierInterface $file_identifier
    ): void {
        $ref_id = $reference_id->toInt();
        $obj_id = $reference_id->toObjectId()->toInt();
        $service = $this->service($obj_id, $ref_id);

        foreach ($service->getFilesByIds([$file_identifier->getIdentifier()]) as $file) {
            $service->downloadFile($file);
        }
    }

    public function getFiles(
        ilExportHandlerConsumerContextInterface $context
    ): ilExportHandlerFileInfoCollectionInterface {
        return $this->buildElements($context);
    }

    public function getFileSelection(
        ilExportHandlerConsumerContextInterface $context,
        ilExportHandlerConsumerFileIdentifierCollectionInterface $file_identifiers
    ): ilExportHandlerFileInfoCollectionInterface {
        return $this->buildElements($context, $file_identifiers->toStringArray());
    }

    public function onExportOptionSelected(
        ilExportHandlerConsumerContextInterface $context
    ): void {
        $service = $this->service($context->exportObject()->getId(), $context->exportObject()->getRefId());
        $result = $service->createFile($this->getServiceExportType());
        if (!empty($result->failures())) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                implode('<br />', $result->failures()),
                true
            );
        } elseif (!empty($result->notes())) {
            $this->tpl->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_INFO,
                implode('<br />', $result->notes()),
                true
            );
        } else {
            $this->tpl->setOnScreenMessage(
                $result->isOk() ? ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS : ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin->txt($result->isOk() ? 'export_file_created' : 'export_file_not_created'),
                true
            );
        }
        $this->ctrl->redirect($context->exportGUIObject());
    }

    private function buildElements(
        ilExportHandlerConsumerContextInterface $context,
        ?array $file_ids = null
    ): ilExportHandlerFileInfoCollectionInterface {
        $service = $this->service($context->exportObject()->getId(), $context->exportObject()->getRefId());

        if (!is_array($file_ids)) {
            $file_ids = [];
            foreach ($service->getFiles() as $file) {
                if ($file->getType() === $this->getServiceExportType()) {
                    $file_ids[] = $file->getFileId();
                }
            }
        }

        $collection_builder = $context->fileCollectionBuilder();
        foreach ($file_ids as $file_id) {
            // todo: better use withFileInfo() and get info from the export service
            // service files may once be stored differently than with irss
            $collection_builder = $collection_builder->withResourceIdentifier(
                $this->resource_storage->manage()->find($file_id),
                $this->data_factory->objId($context->exportObject()->getId()),
                $this
            );
        }
        return $collection_builder->collection();
    }

    /**
     * Lazy load the export service
     * This avoids the plugin's external dependencies being loaded outside the plugin gui
     */
    private function service(int $obj_id, int $ref_id): ExportService
    {
        return $this->plugin->dic()->assessment($obj_id, $this->user_id)->export($ref_id);
    }
}
