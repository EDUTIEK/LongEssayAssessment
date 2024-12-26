<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ilLink;
use ilLongEssayAssessmentPlugin;
use ilMail;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskSettings;

/**
 * Build and send mail notifications via cron and background tasks
 */
class MailNotification extends \ilMailNotification
{
    const REVIEW_NOTIFICATION = 1;
    private ilLongEssayAssessmentPlugin $plugin;
    private TaskSettings $task_settings;

    public function __construct(ilLongEssayAssessmentPlugin $plugin, TaskSettings $task_settings)
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
            // the standard signature may have a false permanent link
            $this->getMail()->appendInstallationSignature(false);

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
        $this->appendBody($this->getWriterStartPermaLink());

        $this->sendMail([$rcp]);
    }


    /**
     * Get a permanent link to the writer start screen
     */
    protected function getWriterStartPermaLink()
    {
        return $this->plugin->getIliasHttpPath() . '/goto.php?target=xlas_' . $this->ref_id . '_writer';
    }
}
