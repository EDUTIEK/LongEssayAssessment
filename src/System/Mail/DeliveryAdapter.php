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

namespace ILIAS\Plugin\LongEssayAssessment\System\Mail;

use ilDBInterface;
use ilMail;
use ilObjUser;

/**
 * Adapter of the ILIAS mail delivery functions for the assessment-service
 */
class DeliveryAdapter implements \Edutiek\AssessmentService\System\Mail\Delivery
{
    private ?ilMail $mail = null;

    public function __construct(
        private readonly ilDBInterface $db,
    ) {
    }

    public function deliver(string $subject, string $body, array $to_ids, array $cc_ids = [], array $bc_ids = []): void
    {
        $ids = array_map(fn($id) => (int) $id, array_merge($to_ids, $cc_ids, $bc_ids));

        $logins = [];
        $query = "SELECT usr_id, login FROM usr_data where usr_id in (" . implode(', ', $ids) . ")";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchObject($result)) {
            $logins[$row->usr_id] = $row->login;
        }

        $filter = fn($array, $keys) => array_filter($array, fn($value, $key) => in_array($key, $keys), ARRAY_FILTER_USE_BOTH);

        $this->getMail()->enqueue(
            implode(', ', $filter($logins, $to_ids)),
            implode(', ', $filter($logins, $cc_ids)),
            implode(', ', $filter($logins, $bc_ids)),
            $subject,
            $body,
            [],
        );

    }

    private function getMail(): ilMail
    {
        return $this->mail ?? new ilMail(ANONYMOUS_USER_ID);
    }
}
