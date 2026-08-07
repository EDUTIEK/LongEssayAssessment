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

use Edutiek\AssessmentService\Assessment\Data\WritingTask;
use ILIAS\Data\ReferenceId;
use ILIAS\Plugin\LongEssayAssessment\BaseGUI;
use Edutiek\AssessmentService\Task\Data\ResourceType;
use Edutiek\AssessmentService\System\File\Disposition;
use ILIAS\Plugin\LongEssayAssessment\System\Data\FileInfo;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService;
use Edutiek\AssessmentService\Assessment\Data\OrgaSettings;
use Edutiek\AssessmentService\Assessment\Data\Writer as Writer;
use Edutiek\AssessmentService\Assessment\WorkingTime\FullService as WorkingTime;
use Edutiek\AssessmentService\EssayTask\Data\WritingSettings as WritingSettings;
use Edutiek\AssessmentService\EssayTask\Data\WritingType;
use ILIAS\StaticURL\Builder\StandardURIBuilder;
use Edutiek\AssessmentService\System\Config\Frontend;
use ILIAS\Plugin\LongEssayAssessment\Jump;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI: ilObjLongEssayAssessmentGUI
 * @ilCtrl_Calls ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI: ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI
 */
class WriterStartGUI extends BaseGUI
{
    private readonly ReadService $perms;
    private readonly OrgaSettings $orga_settings;
    private readonly WritingSettings $writing_settings;
    private Writer $writer;
    private WorkingTime $working_time;


    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getContextId());
        $this->orga_settings = $this->assessment_api->orgaSettings()->get();
        $this->writing_settings = $this->essay_task_api->writingSettings()->get();

        // get or create - permission is already checked
        $this->writer = $this->assessment_api->writer()->oneByUserId($this->user->getId())
            ?? $this->assessment_api->writer()->new()
                ->setAssId($this->object->getAssId())
                ->setUserId($this->user->getId());
        $this->working_time = $this->assessment_api->workingTime($this->writer);
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        match ($cmd) {
            'showStartPage',
            'startWriter',
            'startWritingReview',
            'startWorking',
            'downloadWriterPdf',
            'downloadCorrectedPdf',
            'downloadCorrectionReportsPdf',
            'downloadResourceFile',
            'viewDescription',
            'viewClosingMessage',
            'viewInstructions',
            'deliverInstructions',
            'downloadInstructions',
            'viewSolution',
            'deliverSolution',
            'downloadSolution' => $this->$cmd(),
            default => $this->tpl->setContent('unknown command: ' . $cmd),
        };
    }

    public function showStartPage(): void
    {
        if (!empty($this->orga_settings->getForwardingUrl())
            && !empty($this->writer->getWritingAuthorized())
            && $this->get->has('returned')) {
            $this->ctrl->redirectToURL($this->orga_settings->getForwardingUrl());
            return;
        }

        (new StartPageGUI($this->object, $this->writer, $this))->showPage();
    }

    public function viewDescription(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        $this->add($this->ui_factory->panel()->standard(
            $this->plugin->txt('task_description'),
            $this->plugin_ui_factory->legacy($this->displayText($this->orga_settings->getDescription()))
        ))->show();
    }

    public function viewClosingMessage(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        $this->add($this->ui_factory->panel()->standard(
            $this->plugin->txt('closing_message'),
            $this->plugin_ui_factory->legacy($this->displayText($this->orga_settings->getClosingMessage()))
        ))->show();
    }

    public function viewInstructions(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        if (!$this->perms->canViewInstructions()) {
            return;
        }
        $task_id = $this->get->integer('task_id', 0);
        $task_settings = $this->task_api->settings($task_id)->get();

        $this->add($this->ui_factory->panel()->standard(
            $this->plugin->txt('task_instructions'),
            $this->plugin_ui_factory->legacy($this->displayText($task_settings->getInstructions()))
        ));

        $resource = $this->task_api->resource($task_id)->oneByType(ResourceType::INSTRUCTIONS);
        if ($resource) {
            $this->ctrl->saveParameter($this, 'task_id');
            $this->add($this->ui_factory->panel()->standard(
                $this->plugin->txt('task_instructions_file'),
                [
                    $this->plugin_ui_factory->viewer()->pdf(
                        $this->ctrl->getLinkTarget($this, 'deliverInstructions')
                    ),
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_instructions'),
                        $this->ctrl->getLinkTarget($this, 'downloadInstructions')
                    )
                ]
            ));
        }

        $this->show();
    }

    public function downloadResourceFile(): void
    {
        $identifier = '';
        $resource_id = $this->get->integer('resource_id');
        $task_id = $this->get->integer('task_id');
        if ($resource_id === null || $task_id === null) {
            return;
        }

        $resource_api = $this->task_api->resource($task_id);
        $resource = $resource_api->one($resource_id);
        if (!$resource) {
            $this->raisePermissionError();
        }
        if (!$resource_api->isAvailable($this->orga_settings, $this->working_time, $resource)) {
            $this->raisePermissionError();
        }

        if ($resource->getType() === ResourceType::FILE && is_string($resource->getFileId())) {
            $this->system_api->fileDelivery()->sendFile($resource->getFileId(), Disposition::ATTACHMENT);
        }
    }

    /**
     * Set the working start
     */
    protected function startWorking()
    {
        if ($this->perms->canWrite()) {
            $this->assessment_api->writer()->setWorkingStart($this->writer);

            switch ($this->writing_settings->getWritingType()) {
                case WritingType::ESSAY_EDITOR:
                    $this->ctrl->redirect($this, 'startWriter');

                    // no break
                case WritingType::PDF_UPLOAD:
                    $this->ctrl->redirect($this, 'showStartPage');
            }
        } else {
            $this->raisePermissionError();
        }
    }

    public function startWriter(): void
    {
        if (!$this->perms->canWrite()) {
            $this->raisePermissionError();
        }
        $this->assessment_api->appService()->openWriter(
            $this->object->getContextId(),
            \ilObjLongEssayAssessmentGUI::_link($this->object->getRefId(), Jump::WRITER, true)
        );
    }

    public function startWritingReview(): void
    {
        if (!$this->perms->canReviewWrittenAssessment()) {
            $this->raisePermissionError();
        }
        $this->assessment_api->appService()->openWriter(
            $this->object->getContextId(),
            \ilObjLongEssayAssessmentGUI::_link($this->object->getRefId(), Jump::WRITER, true)
        );
    }

    public function downloadWriterPdf(): void
    {
        if (!$this->perms->canReviewWrittenAssessment()) {
            $this->raisePermissionError();
        }

        $task_id = $this->get->integer('task_id', 0);
        $this->assessment_api->export($this->object->getContextId())->downloadWritings(
            [new WritingTask($this->writer->getId(), $task_id)],
            false
        );
    }

    public function downloadCorrectedPdf(): void
    {
        if (!$this->perms->canReviewCorrectedAssessment()) {
            $this->raisePermissionError();
        }

        $task_id = $this->get->integer('task_id', 0);
        $this->assessment_api->export($this->object->getContextId())->downloadCorrections(
            [new WritingTask($this->writer->getId(), $task_id)],
            false,
            $this->assessment_api->correctionSettings()->get()->getAnonymizeCorrectors()
        );
    }

    public function downloadCorrectionReportsPdf(): void
    {
        if (!$this->perms->canDownloadCorrectionReports()) {
            $this->raisePermissionError();
        }

        $this->assessment_api->export($this->object->getContextId())->downloadReport();
    }


    public function deliverInstructions(Disposition $disposition = Disposition::INLINE): void
    {
        if ($this->perms->canViewInstructions()) {
            $task_id = $this->get->integer('task_id');
            $resource = $this->task_api->resource($task_id)->oneByType(ResourceType::INSTRUCTIONS);
            if ($resource) {
                $this->system_api->fileDelivery()->sendFile($resource->getFileId(), $disposition);
            }
        }
    }

    public function downloadInstructions(): void
    {
        $this->deliverInstructions(Disposition::ATTACHMENT);
    }

    public function viewSolution(): void
    {
        $this->toolbar->addComponent($this->ui_factory->button()->standard(
            $this->lng->txt('back'),
            $this->ctrl->getLinkTarget($this, 'showStartPage')
        ));

        if (!$this->perms->canViewSolution()) {
            return;
        }

        $task_id = $this->get->integer('task_id', 0);
        $task_settings = $this->task_api->settings($task_id)->get();
        $this->add($this->ui_factory->panel()->standard(
            $this->plugin->txt('task_solution'),
            $this->plugin_ui_factory->legacy($this->displayText($task_settings->getSolution()))
        ));

        $resource = $this->task_api->resource($task_id)->oneByType(ResourceType::SOLUTION);
        if ($resource) {
            $this->ctrl->saveParameter($this, 'task_id');
            $this->add($this->ui_factory->panel()->standard(
                $this->plugin->txt('task_solution_file'),
                [
                    $this->plugin_ui_factory->viewer()->pdf(
                        $this->ctrl->getLinkTarget($this, 'deliverSolution')
                    ),
                    $this->ui_factory->link()->standard(
                        $this->plugin->txt('download_solution'),
                        $this->ctrl->getLinkTarget($this, 'downloadSolution')
                    )

                ]
            ));
        }

        $this->show();
    }

    public function deliverSolution(Disposition $disposition = Disposition::INLINE): void
    {
        if ($this->perms->canViewSolution()) {
            $task_id = $this->get->integer('task_id');
            $resource = $this->task_api->resource($task_id)->oneByType(ResourceType::SOLUTION);
            if ($resource) {
                $this->system_api->fileDelivery()->sendFile($resource->getFileId(), $disposition);
            }
        }
    }

    public function downloadSolution(): void
    {
        $this->deliverSolution(Disposition::ATTACHMENT);
    }
}
