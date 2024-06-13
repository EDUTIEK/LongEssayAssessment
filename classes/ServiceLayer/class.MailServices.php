<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

/**
 * Service to create mail notifications
 */
class MailServices
{
    private \ilPlugin $plugin;
    private int $obj_id;
    private int $ref_id;
    private \ILIAS\DI\Container $global_dic;

    public function __construct(\ILIAS\DI\Container $global_dic, \ilPlugin $plugin, int $ref_id)
    {
        $this->global_dic = $global_dic;
        $this->plugin = $plugin;
        $this->ref_id = $ref_id;
    }

    /**
     * Create MailNotification object for a review notification
     */
    public function reviewNotification(array $user_ids): MailNotification
    {
        $this->global_dic->logger()->xlas()->info("Send Review Notification to users: " . implode(', ', $user_ids));
        $mail = new MailNotification($this->plugin);
        $mail->setRefId($this->ref_id);
        $mail->setType(MailNotification::REVIEW_NOTIFICATION);
        $mail->setRecipients($user_ids);
        return $mail;
    }
}