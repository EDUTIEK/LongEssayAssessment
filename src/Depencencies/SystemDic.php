<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\Dependencies;

use DateTimeInterface;
use Edutiek\AssessmentService\System\BackgroundTask\SystemManager as BackgroundTaskManager;
use Edutiek\AssessmentService\System\Mail\Delivery as MailDelivery;
use Edutiek\AssessmentService\System\Session\Storage as SessionStorage;
use ilDatePresentation;
use ilDateTime;
use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\CacheRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\DatabaseRepository;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Generate;
use ILIAS\Plugin\LongEssayAssessment\System\BackgroundTask\Manager as ILIASTaskManager;
use ILIAS\Plugin\LongEssayAssessment\System\Data\Config;
use ILIAS\Plugin\LongEssayAssessment\System\Data\ConfigRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\SetupRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDataRepo;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserDisplayRepo;
use ILIAS\Plugin\LongEssayAssessment\System\File\DeliveryAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\File\Stakeholder;
use ILIAS\Plugin\LongEssayAssessment\System\File\StorageAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\Log\LogAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\Mail\DeliveryAdapter as MailDeliveryAdapter;
use ILIAS\Plugin\LongEssayAssessment\System\Session\SessionAdapter;
use ilTemporaryStakeholder;
use ilUserQuery;
use ilUserUtil;
use InitResourceStorage;

/**
 *  Dependencies of the assessment services component "System"
 */
class SystemDic implements \Edutiek\AssessmentService\System\Api\Dependencies
{
    public function __construct(
        protected Container $dic
    ) {
    }

    public function configRepo(): ConfigRepo
    {
        return new ConfigRepo(
            new CacheRepository(
                new DatabaseRepository(
                    $this->dic->database(),
                    $this->dic[Generate::class]->readModel(Config::class)
                )
            )
        );
    }

    public function setupRepo(): SetupRepo
    {
        return new SetupRepo(
            $this->dic->clientIni(),
            $this->dic->language(),
            $this->dic->cron()->manager()
        );
    }

    public function fileStorage(): StorageAdapter
    {
        return new StorageAdapter(
            $this->dic->resourceStorage()->manage(),
            $this->dic->resourceStorage()->consume(),
            $this->dic->database(),
            new Stakeholder(SYSTEM_USER_ID),
        );
    }

    public function fileDelivery(): DeliveryAdapter
    {
        return new DeliveryAdapter(
            $this->fileStorage(),
            $this->dic->http(),
            $this->setupRepo()->one()->getAbsoluteTempPath()
        );
    }

    public function tempStorage(): StorageAdapter
    {
        return new StorageAdapter(
            $this->dic->resourceStorage()->manage(),
            $this->dic->resourceStorage()->consume(),
            $this->dic->database(),
            new ilTemporaryStakeholder(),
        );
    }

    public function tempDelivery(): DeliveryAdapter
    {
        return new DeliveryAdapter(
            $this->tempStorage(),
            $this->dic->http(),
            $this->setupRepo()->one()->getAbsoluteTempPath()
        );
    }

    public function userDataRepo(): UserDataRepo
    {
        return new UserDataRepo(
            $this->dic->database(),
            $this->dic->language(),
            new ilUserQuery()
        );
    }

    public function userDisplayRepo(): UserDisplayRepo
    {
        return new UserDisplayRepo(new ilUserUtil());
    }

    public function formatDate(DateTimeInterface $date): string
    {
        return ilDatePresentation::formatDate(new ilDateTime($date->getTimestamp(), IL_CAL_UNIX));
    }

    public function backgroundTaskManager(): BackgroundTaskManager
    {
        return new ILIASTaskManager(
            $this->dic->backgroundTasks()->taskFactory(),
            $this->dic->backgroundTasks()->taskManager(),
            $this->dic->user(),
        );
    }

    public function sessionStorage(): SessionStorage
    {
        return new SessionAdapter();
    }

    public function log(): LogAdapter
    {
        return new LogAdapter($this->dic->logger()->xlas());
    }

    public function mailDelivery(): MailDelivery
    {
        return new MailDeliveryAdapter(
            $this->dic->database()
        );
    }
}
