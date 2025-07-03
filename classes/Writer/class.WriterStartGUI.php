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

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\Task\Data\ResourceAvailability;
use Edutiek\AssessmentService\System\File\Disposition;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\Writer as Writer;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI: ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI
 */
class WriterStartGUI extends BaseGUI
{
    private readonly ReadService $perms;
    private readonly OrgaSettings $orga_settings;
    private Writer $writer;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getId());
        $this->orga_settings = $this->assessment_api->orgaSettings()->get();

        // get or create - permission is already checked
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        match ($cmd) {
            'showStartPage',
            'startWriter',
            'startWritingReview',
            'downloadWriterPdf',
            'downloadCorrectedPdf',
            'downloadCorrectionReportsPdf',
            'downloadResourceFile',
            'viewDescription',
            'viewClosingMessage',
            'viewInstructions',
            'downloadInstructions',
            'viewSolution',
            'downloadSolution' => $this->$cmd(),
            default => $this->tpl->setContent('unknown command: ' . $cmd),
        };
    }

    public function showStartPage(): void
    {


        // todo: add forwarding url from version 3
//        if(!empty($this->orga_settings->getForwardingUrl()) && !empty($this->writer->getWritingAuthorized()) && $this->get->has('returned')) {
//            $this->ctrl->redirectToURL($this->orga_settings->getForwardingUrl());
//            return;
//        }

        (new StartPageGUI($this->object, $this->writer, $this, $this->isResourceAvailable(...)))->showPage();
    }

    public function viewDescription(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        $this->renderContent($this->ui_factory->panel()->standard(
            $this->plugin->txt('task_description'),
            $this->ui_factory->legacy($this->displayText($this->orga_settings->getDescription()))
        ));
    }

    public function viewClosingMessage(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        $this->renderContent($this->ui_factory->panel()->standard(
            $this->plugin->txt('closing_message'),
            $this->ui_factory->legacy($this->displayText($this->orga_settings->getClosingMessage()))
        ));
    }

    public function viewInstructions(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        if (time() >= $this->orga_settings->getWritingStart()?->getTimestamp()) {
            return;
        }
        $task_id = $this->get->integer('task_id');
        if (!$task_id) {
            return;
        }
        $task_settings = $this->task_api->settings($task_id)->get();

        $this->renderContent($this->ui_factory->panel()->standard(
            $this->plugin->txt('task_instructions'),
            $this->ui_factory->legacy($this->displayText($task_settings->getInstructions()))
        ));
    }

    public function downloadResourceFile(): void
    {
        $identifier = '';
        $resource_id = $this->get->integer('resource_id');
        $task_id = $this->get->integer('task_id');
        if ($resource_id === null || $task_id === null) {
            return;
        }

        $resource = $this->task_api->resource($task_id)->one($resource_id);
        if (!$resource) {
            $this->raisePermissionError();
        }
        if (!$this->isResourceAvailable($resource)) {
            $this->raisePermissionError();
        }

        if ($resource->getType() === ResourceType::FILE && is_string($resource->getFileId())) {
            $this->system_api->fileDelivery()->sendFile($resource->getFileId(), Disposition::ATTACHMENT);
        }
    }

    public function startWriter(): void
    {
        if (!$this->perms->canWrite()) {
            $this->raisePermissionError();
        }
        $this->assessment_api->writerApp($this->object->getId())->open();

    }

    public function startWritingReview(): void
    {
        if (!$this->perms->canReviewWrittenEssay()) {
            $this->raisePermissionError();
        }
        $this->assessment_api->writerApp($this->object->getId())->open();
    }

    public function downloadWriterPdf(): void
    {
        if (!$this->perms->canViewWriterScreen()) {
            $this->raisePermissionError();
        }
        // @Todo
        // $service = $this->localDI->getWriterAdminService($this->object->getId());
        // $repoWriter = $this->localDI->getWriterRepo()->getWriterByUserIdAndTaskId($this->dic->user()->getId(), $this->object->getId());
        // $content = $service->getWritingAsPdf($this->object, $repoWriter)

        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-writing.pdf';
        $file_info = new FileInfo();
        $file_info->setFileName($filename);
        $file_info->setMimeType('application/pdf');
        $this->system_api->fileDelivery()->sendData(
            '',
            Disposition::ATTACHMENT,
            $file_info
        );
    }

    public function downloadCorrectedPdf(): void
    {
        if (!$this->perms->canReviewCorrectedEssay()) {
            $this->raisePermissionError();
        }
        // @Todo
        // $service = $this->localDI->getCorrectorAdminService($this->object->getId());
        // $repoWriter = $this->localDI->getWriterRepo()->getWriterByUserIdAndTaskId($this->dic->user()->getId(), $this->object->getId());
        // $content = $service->getCorrectionAsPdf($this->object, $repoWriter, null, false, true);

        $filename = 'task' . $this->object->getId() . '_writer' . $repoWriter->getId(). '-correction.pdf';
        $file_info = new FileInfo();
        $file_info->setFileName($filename);
        $file_info->setMimeType('application/pdf');
        $this->system_api->fileDelivery()->sendData(
            '',
            Disposition::ATTACHMENT,
            $file_info
        );
    }

    public function downloadCorrectionReportsPdf(): void
    {
        if (!$this->perms->canDownloadCorrectionReports()) {
            $this->raisePermissionError();
        }
        $filename = 'task' . $this->object->getId() . '-reports.pdf';
        $file_info = new FileInfo();
        $file_info->setFileName($filename);
        $file_info->setMimeType('application/pdf');
        $this->system_api->fileDelivery()->sendData(
            $this->pdf(),
            Disposition::ATTACHMENT,
            $file_info
        );
        
    }

    public function downloadInstructions(): void
    {
        if (time() < $this->orga_settings->getWritingStart()?->getTimestamp()) {
            $task_id = $this->get->integer('task_id');
            $resource = $this->task_api->resource($task_id)->oneByType(ResourceType::SOLUTION);
            if ($resource) {
                $this->system_api->fileDelivery()->sendFile($resource->getFileId(), Disposition::ATTACHMENT);
            }
        }
    }

    public function viewSolution(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        if ($this->perms->canViewSolution()) {
            $task_id = $this->get->integer('task_id');
            $task_settings = $this->task_api->settings($task_id)->get();
            $this->renderContent($this->ui_factory->panel()->standard(
                $this->plugin->txt('task_solution'),
                $this->ui_factory->legacy($this->displayText($task_settings->getSolution()))
            ));
        }
    }

    public function downloadSolution(): void
    {
        if ($this->perms->canViewSolution()) {
            $task_id = $this->get->integer('task_id');
            $resource = $this->task_api->resource($task_id)->oneByType(ResourceType::SOLUTION);
            if ($resource) {
                $this->system_api->fileDelivery()->sendFile($resource->getFileId(), Disposition::ATTACHMENT);
            }
        }
    }

    /**
     * todo: move to a service function
     */
    private function isResourceAvailable($resource): bool
    {
        if ($resource->getAvailability() === ResourceAvailability::BEFORE) {
            return true;
        }

        if ($resource->getAvailability() === ResourceAvailability::DURING
            && time() < $this->orga_settings->getWritingStart()->getTimestamp()) {
            return true;
        }

        return $resource->getAvailability() === ResourceAvailability::AFTER
            && $this->orga_settings->getSolutionAvailable()
            && time() < $this->orga_settings->getSolutionAvailableDate()->getTimestamp();
    }

    private function pdf(): string
    {
        // @Todo
        return '';
        // $context = new CorrectorContext();
        // $context->init((string) $this->dic->user()->getId(), (string) $object->getRefId());
        // $service = new Service($context);

        // $elements = [];
        // foreach ($this->correctorRepo->getCorrectorsByTaskId($this->task_id) as $corrector) {
        //     if (!empty($corrector->getCorrectionReport())) {
        //         $elements[] = new PdfHtml($corrector->getCorrectionReport() . '<hr>');
        //     }
        // }

        // return $service->getPdfGeneration()->generatePdf(
        //     [$service->getStandardPdfPart($elements)],
        //     '',
        //     '',
        //     $object->getTitle(),
        //     $this->plugin->txt('correction_reports')
        // );
    }

    private function getWritingAsPdf(ilObjLongEssayAssessment $object, $repoWriter, bool $anonymous = false, bool $rawContent = false, bool $onlyText = false): string
    {
        $context = new WriterContext();
        $context->init((string) $repoWriter->getUserId(), (string) $object->getRefId());

        $writingTask = $context->getWritingTask();
        if ($anonymous) {
            $writingTask = $writingTask->withWriterName($repoWriter->getPseudonym());
        }
        $writtenEssay = $context->getWrittenEssay();

        $service = new Service($context);
        return $service->getWritingAsPdf($writingTask, $writtenEssay, $rawContent, $onlyText);
    }
}
