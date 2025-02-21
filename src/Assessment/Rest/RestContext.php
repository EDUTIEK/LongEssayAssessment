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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Rest;

use ilDBInterface;
use ilIniFile;
use ilSession;
use ILIAS\HTTP\Services;

readonly class RestContext implements \Edutiek\AssessmentService\Assessment\Apps\RestContext
{
    public function __construct(
        private ilIniFile $client_ini,
        private ilDBInterface $db,
        private Services $http
    ) {
    }

    /**
     * Get the route of the REST call
     */
    public function getRoute(): string
    {
        $params = $this->http->request()->getServerParams();
        return $params['PATH_INFO'];
    }

    /**
     * Get the params of the REST call
     */
    public function getParams(): array
    {
        return $this->http->request()->getQueryParams();
    }

    /**
     * Do additional ILIAS initialisations for a REST call
     */
    public function initCall(int $ass_id, int $context_id, int $user_id): void
    {
        // REST calls from the web app should not write the user session of ILIAS in general
        // Session expire is set for specific calls that indicate a user activity
        ilSession::enableWebAccessWithoutSession(true);

        // Init a missing user, access handling, html, language
        RestInit::initRestUser($user_id);
    }

    /**
     * Extend the session of an authenticated user
     * A user may have parallel active sessions
     * We cannot determine the session because the service does not provide its session_id
     * So continue all active sessions, and try to filter them by ip if the ip is stored in ilias
     */
    public function setAlive(int $user_id): void
    {
        if ($this->client_ini->readVariable("session", "save_ip")) {
            $session_ids = $this->getActiveSessionIds((int) $user_id, (string) $_SERVER["REMOTE_ADDR"]);
        } else {
            $session_ids = $this->getActiveSessionIds($user_id);
        }

        foreach ($session_ids as $id) {
            $this->setSessionExpires($id, ilSession::getExpireValue());
        }
    }

    /**
     * Send a response to the application
     */
    public function sendResponse(int $status_code, string $body): never
    {
        $response = $this->http->response()->withStatus($status_code);
        $stream = $response->getBody();
        $stream->write($body);

        $this->http->saveResponse($response);
        $this->http->sendResponse();
        $this->http->close();
        exit;
    }

    /**
     * Get the ids of active sessions for a user, and (optional) a specific ip address
     * @return string[]
     */
    private function getActiveSessionIds(int $user_id, ?string $ip_address = null): array
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
    private function setSessionExpires(string $session_id, int $expires): void
    {
        $query = "UPDATE usr_session SET expires = " . $this->db->quote($expires, 'integer')
            . " WHERE session_id = " . $this->db->quote($session_id, 'text');

        $this->db->manipulate($query);
    }
}
