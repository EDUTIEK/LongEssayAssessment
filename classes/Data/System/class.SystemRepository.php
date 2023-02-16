<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data\System;

class SystemRepository
{
    private \ilDBInterface $database;

    public function __construct(\ilDBInterface $database)
    {
        $this->database = $database;

    }

    /**
     * Get the ids of active sessions for a user, and (optional) a specific ip address
     * @return string[]
     */
    public function getActiveSessionIds(int $user_id, ?string $ip_address = null) : array
    {
        $query = "SELECT session_id FROM usr_session WHERE user_id = " . $this->database->quote($user_id, 'integer')
        . " AND expires > " . $this->database->quote(time(), 'integer');

        if (!empty($ip_address)) {
            $query .= " AND remote_addr = " . $this->database->quote($ip_address, 'text');
        }

        $result = $this->database->query($query);

        $ids = [];
        while ($row = $this->database->fetchAssoc($result)) {
            $ids[] = (string) $row['session_id'];
        }

        return $ids;
    }

    /**
     * Set the expiration time of a specific user session
     */
    public function setSessionExpires(string $session_id, int $expires) : void
    {
        $query = "UPDATE usr_session SET expires = "  . $this->database->quote($expires, 'integer')
            ." WHERE session_id = ". $this->database->quote($session_id, 'text');

        $this->database->manipulate($query);
    }
}