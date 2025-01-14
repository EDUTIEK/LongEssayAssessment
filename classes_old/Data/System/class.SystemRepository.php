<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\System;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo;

class SystemRepository extends RecordRepo
{
    public function __construct(\ilDBInterface $db, \ilLogger $logger)
    {
        parent::__construct($db, $logger);
    }

    /**
     * @return PluginConfig|null
     */
    public function getPluginConfig() : ?RecordData
    {
        return $this->getSingleRecord('SELECT * FROM xlas_plugin_config', PluginConfig::model(), PluginConfig::model());
    }


    /**
     * Get the ids of active sessions for a user, and (optional) a specific ip address
     * @return string[]
     */
    public function getActiveSessionIds(int $user_id, ?string $ip_address = null) : array
    {
        $query = "SELECT session_id FROM usr_session WHERE user_id = " . $this->db->quote($user_id, 'integer')
        . " AND expires > " . $this->db->quote(time(), 'integer');

        if (!empty($ip_address)) {
            $query .= " AND remote_addr = " . $this->db->quote($ip_address, 'text');
        }

        $result = $this->db->query($query);

        $ids = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $ids[] = (string) $row['session_id'];
        }

        return $ids;
    }

    /**
     * Set the expiration time of a specific user session
     */
    public function setSessionExpires(string $session_id, int $expires) : void
    {
        $query = "UPDATE usr_session SET expires = "  . $this->db->quote($expires, 'integer')
            ." WHERE session_id = ". $this->db->quote($session_id, 'text');

        $this->db->manipulate($query);
    }

    /**
     * Save record data of an allowed type
     * @param PluginConfig $record
     */
    public function save(RecordData $record)
    {
        $this->replaceRecord($record);
    }
}