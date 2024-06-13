<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ilMail;
use ilPlugin;

/**
 * Build and send mail notifications via cron and background tasks
 */
class MailNotification extends \ilMailNotification
{
    const REVIEW_NOTIFICATION = 1;
    private ilPlugin $plugin;

    public function __construct(ilPlugin $plugin)
    {
        parent::__construct(false);
        $this->plugin = $plugin;
        $this->setLangModules([$this->plugin->getPrefix()]);
    }

    protected function getLanguageText(string $a_keyword, bool $plugin = false): string
    {
        $a_default_lang_fallback_mod = "";
        if($plugin) {
            $a_keyword = $this->plugin->getPrefix() . "_" . $a_keyword;
            $a_default_lang_fallback_mod = $this->plugin->getPrefix();
        }
        return str_replace('\n', "\n", $this->getLanguage()->txt($a_keyword), $a_default_lang_fallback_mod);
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
            sprintf($this->getLanguageText("mail_review_notification_subject", true), $this->getObjectTitle())
        );

        $this->setBody(ilMail::getSalutation($rcp, $this->getLanguage()));
        $this->appendBody("\n\n");
        $this->appendBody($this->getLanguageText("mail_review_notification_body", true));
        $this->appendBody("\n\n");
        $this->appendBody($this->getLanguageText("mail_permanent_link", true));
        $this->appendBody("\n\n");
        $this->appendBody($this->createPermanentLink([], "_writer"));

        $this->sendMail([$rcp]);
    }

}
