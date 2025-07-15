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
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Edutiek\AssessmentService\Assessment\Data\Writer;
use ILIAS\Plugin\LongEssayAssessment\BaseObjectData;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo as Task;
use Edutiek\AssessmentService\Task\Resource\FullService as ResourceApi;
use ILIAS\Data\DataSize;
use ILIAS\Plugin\LongEssayAssessment\Handler\Upload;
use Edutiek\AssessmentService\System\File\Disposition;
use ILIAS\HTTP\StatusCode;

/**
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI: ilObjLongEssayAssessmentGUI
 */
class WriterUploadGUI extends BaseGUI
{
    private readonly Permissions $perms;
    private readonly Writer $writer;

    public function __construct(BaseObjectData $object)
    {
        parent::__construct($object);
        $this->perms = $this->assessment_api->permissions($this->object->getId());
        $this->writer = $this->assessment_api->writer()->getByUserId($this->user->getId());
    }

    public function executeCommand(): void
    {
        $this->{$this->ctrl->getCmd()}();
    }

    
    public function reviewPdf(): void
    {
        if (!$this->perms->canWrite() && !$this->perms->canReviewWrittenAssessment()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
        }

        $this->renderContent($this->mapEssays(function (Essay $essay, Task $task, ResourceApi $resource_api): array {
            $resource = $essay->getPdfVersion() !== null ?
                $resource_api->one((int) $essay->getPdfVersion()) : // @Todo: Check if this is correct.
                null;

            if ($resource === null) { // Should probably not fail completely if just one resource could not be found.
                $this->failure($this->plugin->txt('pdf_version_not_found'), true);
                $this->ctrl->redirectToURL($this->ctrl->getLinkTargetByClass(WriterStartGUI::class));
            }

            $this->info($this->plugin->txt('writer_authorize_pdf_info'));

            $this->ctrl->setParameter($this, 'task', $task->getId());
            $this->ctrl->setParameter($this, 'resource', $resource->getId());
            
            return [
                $this->ui_factory->panel()->standard(
                    $this->plugin->txt('writer_review_pdf'),
                    [
                        $this->plugin->dic()->uiFactory()->viewer()->pdf(
                            $this->ctrl->getLinkTarget($this, 'deliverPdf'),
                            $resource->getTitle()
                        )
                    ]
                ),
                $this->ui_factory->button()->primary(
                    $this->plugin->txt('writer_authorize_pdf'),
                    $this->ctrl->getLinkTarget($this, 'authorizePdf')
                ),
                $this->ui_factory->button()->standard(
                    $this->lng->txt('cancel'),
                    $this->ctrl->getLinkTargetByClass(WriterStartGUI::class)
                )
            ];
        }));
    }

    public function uploadPdf(): void
    {
        if (!$this->perms->canWrite()) {
            $this->failure($this->lng->txt('permission_denied'), true);
            $this->ctrl->redirectByClass(WriterStartGUI::class);
        }

        $entries = array_column($this->mapEssays(function (Essay $essay, Task $task, ResourceApi $resource_api): array {
            $components = [];

            if (
                $essay->getPdfVersion() !== null &&
                ($resource = $resource_api->one((int) $essay->getPdfVersion())) // @Todo: Check if this is correct.
            ) {
                $components[] = $this->ui_factory->panel()->standard($this->plugin->txt('existing_file'), [
                    $this->ui_factory->item()->standard(
                        $this->ui_factory->link()->standard(
                            $resource->getTitle(),
                            $this->ctrl->getLinkTarget($this, "downloadPdf")
                        )
                    )->withLeadIcon($this->ui_factory->symbol()->icon()->standard('file', '', 'medium'))
                        ->withProperties([$this->lng->txt('filesize') => (string) new DataSize(
                            $this->system_api->fileStorage()->getFileInfo($resource->getFileId())->getSize(), DataSize::Byte
                        )])
                ]);

                $components[] = $this->ui_factory->button()->standard(
                    $this->plugin->txt('delete_file'),
                    $this->ctrl->getLinkTarget($this, 'deletePdf')
                );

                return [
                    'key' => $essay->getId(),
                    'component' => $this->ui_factory->input()->field()->file(
                        new Upload($this->linkTo(...)), // @todo correct links
                        $this->plugin->txt('new_file'),
                        '', // @todo: $this->localDI->getUIService()->getMaxFileSizeString()
                    )->withAcceptedMimeTypes(['application/pdf']),
                ];
            }

            return $components;
        }), 'component', 'key');

        $form = $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'uploadPdf', '', true),
            $entries
        )->withSubmitLabel($this->lng->txt('upload'));

        $components = [];

        // $components[] = $this->ui_factory->panel()->standard(
        //     $this->plugin->txt($essay->getPdfVersion() === null ? 'upload_file' : 'replace_file'),
        //     [$form]
        // );

        $form = $this->withFormData($form, function (array $data): void {
            // $this->writer_admin_service->handlePDFVersionInput($this->object->getRefId(), $essay, $file_id);
            $this->ctrl->redirect($this, 'reviewPdf');
        });

        $this->renderContent($form);
    }

    public function upload(): void
    {
        
    }

    public function deliverPdf(): void
    {
        // @Todo: Either add access check or add $this->essay_task_api->essay()->one(...) method.
        
        // $essay = $this->essay_task_api->essay()->one($this->get->integer('essay_id')) ?? $this->notFound();
        // $essay->getPdfVersion() ?? $this->notFound();
        // $task = $this->task_api->manager()->one($essay->getTaskId()) ?? $this->notFound();
        // $resource_api = $this->task_api->resource($task->getId());
        // $resource = $resource_api->one((int) $essay->getPdfVersion()) ?? $this->notFound();
        $task = $this->task_api->manager()->one($this->get->integer('task') ?? $this->notFound()) ?? $this->notFound();
        $resource_api = $this->task_api->resource($task->getId());
        $resource = $resource_api->one($this->get->integer('resource') ?? $this->notFound()) ?? $this->notFound();
        $this->system_api->fileDelivery()->sendFile($resource->getFileId(), Disposition::ATTACHMENT);
    }

    private function mapEssays(callable $proc): array
    {
        return array_map(function (Essay $essay) use ($proc) {
            $task = $this->task_api->manager()->one($essay->getTaskId());
            return $proc($essay, $task, $this->task_api->resource($task->getId()));
        }, $this->essay_task_api->essay()->allByWriterId($this->writer->getId()));
    }

    private function linkTo(string $cmd): string
    {
        return $this->ctrl->getLinkTarget($this, $cmd);
    }

    private function notFound(): never
    {
        $response = $this->http->response()->withStatus(StatusCode::HTTP_NOT_FOUND);
        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
    }
}
