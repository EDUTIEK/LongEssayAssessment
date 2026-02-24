<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\BackgroundTask;

use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use ILIAS\BackgroundTasks\TaskManager;
use ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Types\Type;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\System\File\Storage as Storage;
use Edutiek\AssessmentService\System\File\Delivery as Delivery;
use Edutiek\AssessmentService\System\File\Disposition;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use ILIAS\UI\Component\Table\Column\Boolean;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;

/**
 * Download a file created in  a background task
 */
class Download extends AbstractUserInteraction
{
    private const OPTION_DOWNLOAD = 'download';
    private const OPTION_REMOVE = 'remove';

    private Storage $storage;
    private Delivery $delivery;

    public function __construct()
    {
        $plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->storage = $plugin->dic()->system()->fileStorage();
        $this->delivery = $plugin->dic()->system()->fileDelivery();
    }

    /**
     * @param Value[] $input The input value of this task.
     * @return Option[] Options are buttons the user can press on this interaction.
     */
    public function getOptions(array $input): array
    {
        return [
            new UserInteractionOption("download", self::OPTION_DOWNLOAD)
        ];
    }

    public function getRemoveOption(): Option
    {
        return new UserInteractionOption('remove', self::OPTION_REMOVE);
    }

    /**
     * @param array  $input                The input value of this task.
     * @param Option $user_selected_option The Option the user chose.
     * @param Bucket $bucket               Notify the bucket about your progress!
     */
    public function interaction(array $input, Option $user_selected_option, Bucket $bucket): Value
    {
        $file_id = (string) $input[0]->getValue();
        $delete = (bool) $input[1]->getValue();

        switch ($user_selected_option->getValue()) {
            case self::OPTION_DOWNLOAD:
                $this->delivery->sendFile($file_id, Disposition::ATTACHMENT);
                break;
            case self::OPTION_REMOVE:
                if ($delete) {
                    $this->storage->deleteFile($file_id);
                }
                break;
        }

        return new StringValue();
    }

    public function getInputTypes(): array
    {
        return [
            new SingleType(StringValue::class),
            new SingleType(BooleanValue::class),
        ];
    }

    public function getOutputType(): Type
    {
        return new SingleType(StringValue::class);
    }
}
