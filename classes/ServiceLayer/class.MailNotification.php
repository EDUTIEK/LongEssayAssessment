<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ilMail;
use ilPlugin;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;

/**
 * Build and send mail notifications via cron and background tasks
 */
class MailNotification extends \ilMailNotification
{
    const REVIEW_NOTIFICATION = 1;
    private ilPlugin $plugin;
    private TaskSettings $task_settings;

    public function __construct(ilPlugin $plugin, TaskSettings $task_settings)
    {
        parent::__construct(false);
        $this->plugin = $plugin;
        $this->task_settings = $task_settings;
        $this->setLangModules([$this->plugin->getPrefix()]);
    }

    protected function getLanguageTextPlugin(string $a_keyword): string
    {
        $a_keyword = $this->plugin->getPrefix() . "_" . $a_keyword;
        return str_replace('\n', "\n", $this->getLanguage()->txt($a_keyword));
    }

    public function send()
    {
        foreach($this->getRecipients() as $rcp) {
            $this->initLanguage($rcp);
            $this->initMail();
            $this->getMail()->appendInstallationSignature(true);

            switch($this->getType()) {
                case self::REVIEW_NOTIFICATION: $this->sendReviewMail($rcp);
                    break;
            }
        }
    }

    private function sendReviewMail(int $rcp)
    {
        $this->setSubject(
            sprintf($this->getLanguageTextPlugin("mail_review_notification_subject"), $this->getObjectTitle())
        );

        $this->setBody(ilMail::getSalutation($rcp, $this->getLanguage()));
        $this->appendBody("\n\n");
        $this->appendBody($this->getLanguageTextPlugin("mail_review_notification_body"));
        $this->appendBody("\n\n");

        if(!empty($this->task_settings->getReviewNotificationText())) {
            $this->appendBody($this->task_settings->getReviewNotificationText());
            $this->appendBody("\n\n");
        }

        $this->appendBody($this->getLanguageTextPlugin("mail_permanent_link"));
        $this->appendBody("\n\n");
        $this->appendBody($this->createPermanentLink([], "_writer"));

        $this->sendMail([$rcp]);
    }

}
