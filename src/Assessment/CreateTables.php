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

namespace ILIAS\Plugin\LongEssayAssessment\Data\Assessment;

use ilDBInterface;
use ilDBConstants;

class CreateTables
{
    public function __construct(private readonly ilDBInterface $db)
    {
    }

    public function all(): void
    {
        $this->alert();
        $this->corrector();
        $this->correctorSetting();
        $this->gradeLevel();
        $this->location();
        $this->logEntry();
        $this->pdfSettings();
        $this->settings();
        $this->token();
        $this->writer();
    }

    public function alert(): void
    {
        $this->db->createTable('xlas_as_alert', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'title' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 255
            ],
            'message' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true
            ],
            'writer_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'shown_from' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'shown_until' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ]
        ]);
    }
    public function corrector(): void
    {
        $this->db->createTable('xlas_as_corrector', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'user_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'correction_report' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function correctorSetting(): void
    {
        $this->db->createTable('xlas_as_corr_setting', [
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'required_correctors' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'max_auto_distance' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'mutual_visibility' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'assign_mode' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'stitch_when_distance' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'stitch_when_decimals' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'anonymize_correctors' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'reports_enabled' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'reports_available_start' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ]
        ]);
    }
    public function gradeLevel(): void
    {
        $this->db->createTable('xlas_as_grade_level', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'min_points' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'grade' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 255
            ],
            'code' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 255
            ],
            'passed' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 1
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function location(): void
    {
        $this->db->createTable('xlas_as_location', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'title' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 255
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function logEntry(): void
    {
        $this->db->createTable('xlas_as_log_entry', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'timestamp' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'category' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 255
            ],
            'entry' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function pdfSettings(): void
    {
        $this->db->createTable('xlas_as_pdf_settings', [
            'add_header' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'add_footer' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'top_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'bottom_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'left_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'right_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function settings(): void
    {
        $this->db->createTable('xlas_as_settings', [
            'online' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'participation_type' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 10
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'description' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'closing_message' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'writing_start' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'writing_end' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'writing_limit_minutes' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'correction_start' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'correction_end' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'review_start' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'review_end' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'keep_available' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'solution_available_date' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'result_available_type' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 10
            ],
            'result_available_date' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'solution_available' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'review_enabled' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'review_notification' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'review_notif_text' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'statistics_available' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function token(): void
    {
        $this->db->createTable('xlas_as_token', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'user_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'token' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'ip' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'purpose' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 10
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'valid_until' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ]
        ]);
    }
    public function writer(): void
    {
        $this->db->createTable('xlas_as_writer', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'user_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'pseudonym' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 255
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'earliest_start' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'latest_end' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'time_limit_minutes' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'working_start' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'final_points' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => false
            ],
            'final_grade_level_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'writing_authorized' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'writing_authorized_by' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'correction_finalized_by' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'writing_excluded' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'writing_excluded_by' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'stitch_comment' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'location' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'review_notification' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
}
