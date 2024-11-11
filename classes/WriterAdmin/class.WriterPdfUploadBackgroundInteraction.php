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

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use ILIAS\BackgroundTasks\Types\Type;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Implementation\Values\ThunkValue;
use ilObjectFactory;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\ResourceStorage\Services;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ilLongEssayAssessmentPlugin;
use ilLink;

class WriterPdfUploadBackgroundInteraction extends AbstractUserInteraction
{
    protected Container $dic;
    protected \ilLongEssayAssessmentPlugin $plugin;
    protected LongEssayAssessmentDI $local;
    protected Services $resource_storage;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->local = LongEssayAssessmentDI::getInstance();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->resource_storage = $this->dic->resourceStorage();
    }

    public function getOptions(array $input): array
    {
        return [
            $this->getRemoveOption()
        ];
    }


    public function getInputTypes(): array
    {
        return [
            new SingleType(BooleanValue::class),
            new SingleType(IntegerValue::class),
            new SingleType(IntegerValue::class),
        ];
    }

    public function getOutputType(): Type
    {
        return new SingleType(ThunkValue::class);
    }

    public function interaction(
        array $input,
        Option $user_selected_option,
        Bucket $bucket
    ): Value {

        $success = (bool) $input[0]->getValue();
        $ref_id = (int) $input[1]->getValue();
        $essay_id = (int) $input[2]->getValue();

        $object = ilObjectFactory::getInstanceByRefId($ref_id);
        $essay = $this->local->getEssayRepo()->getEssayById($essay_id);
        $writer = $this->local->getWriterRepo()->getWriterById($essay->getWriterId());
        $service = $this->local->getWriterAdminService($essay->getTaskId());

        if ($essay->getPdfVersion() !== null) {
            $this->local->services()->common()->fileHelper()->deliverResource($essay->getPdfVersion(), 'attachment');
        } else {
            $this->dic->ctrl()->redirectToURL(ilLink::_getLink($ref_id));
        }

        return new ThunkValue();
    }

    public function getMessage(array $input): string
    {
        $success = (bool) $input[0]->getValue();

        return $this->plugin->txt($success ? 'writer_upload_pdf_bt_success' : 'writer_upload_pdf_bt_failure');
    }

    public function canBeSkipped(array $input): bool
    {
        $success = (bool) $input[0]->getValue();
        return $success;
    }
}
