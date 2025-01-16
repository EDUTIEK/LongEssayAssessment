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

namespace ILIAS\Plugin\LongEssayAssessment\Data\EssayTask;

use ilDBInterface;
use ilDBConstants;

class CreateTables
{
    public function __construct(private readonly ilDBInterface $db)
    {
    }

    public function all(): void
    {
        $this->correctorComment();
        $this->correctorAssignmentPreference();
        $this->correctorPoints();
        $this->correctorSetting();
        $this->correctorSummary();
        $this->correctorTaskPreference();
        $this->essay();
        $this->ratingCriteria();
        $this->taskSetting();
        $this->writeSetting();
        $this->essayImage();
        $this->writerHistory();
        $this->writerNotice();
        $this->writerPrefs();
    }

    public function correctorComment(): void
    {
        $this->db->createTable('xlas_et_corr_comm', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'essay_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'comment' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'start_position' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'end_position' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'rating' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 16
            ],
            'corrector_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'parent_number' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'marks' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 4000
            ]
        ]);
    }
    public function correctorAssignmentPreference(): void
    {
        $this->db->createTable('xles_et_corr_ass_pref', [
            'corrector_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'essay_page_zoom' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'essay_text_zoom' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'summary_text_zoom' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'include_comments' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_comment_ratings' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_comment_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_criteria_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function correctorPoints(): void
    {
        $this->db->createTable('xlas_et_corr_points', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'comment_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'criterion_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'essay_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'corrector_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'points' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ]
        ]);
    }
    public function correctorSetting(): void
    {
        $this->db->createTable('xlas_et_corr_setting', [
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'criteria_mode' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'positive_rating' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'negative_rating' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'fixed_inclusions' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_comments' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_comment_ratings' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_comment_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'include_criteria_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function correctorSummary(): void
    {
        $this->db->createTable('xlas_et_corr_summary', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'essay_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'corrector_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'summary_text' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'points' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => false
            ],
            'last_change' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'include_comments' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'include_comment_ratings' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'include_comment_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'include_criteria_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'corection_authorized' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'correction_authorized_by' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ]
        ]);
    }
    public function correctorTaskPreference(): void
    {
        $this->db->createTable('xlas_et_corr_task_pref', [
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'criterion_copy' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 1
            ]
        ]);
    }
    public function essay(): void
    {
        $this->db->createTable('xlas_et_essay', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'uuid' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'writer_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'written_text' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'raw_text_hash' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'pdf_version' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 50
            ],
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'last_change' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'service_version' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'first_change' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ]
        ]);
    }
    public function ratingCriteria(): void
    {
        $this->db->createTable('xlas_et_rating_crit', [
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
            'description' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'corrector_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'general' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function taskSetting(): void
    {
        $this->db->createTable('xlas_et_task_setting', [
            'task_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'max_points' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
    public function writeSetting(): void
    {
        $this->db->createTable('xlas_et_write_setting', [
            'headline_scheme' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'formatting_options' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 250
            ],
            'notice_boards' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'copy_allowed' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'add_paragraph_numbers' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'add_correction_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'left_correction_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'right_correction_margin' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'allow_spellcheck' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'ass_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'writing_type' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ]
        ]);
    }
    public function essayImage(): void
    {
        $this->db->createTable('xlas_et_essay_image', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'essay_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'page_no' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'width' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'height' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'mime' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 255
            ],
            'thumb_width' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'thumb_height' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => false,
                'length' => 4
            ],
            'thumb_mime' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 255
            ],
            'file_id' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => true,
                'length' => 50
            ],
            'thumb_id' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 50
            ]
        ]);
    }
    public function writerHistory(): void
    {
        $this->db->createTable('xlas_et_writer_history', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'essay_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'timestamp' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ],
            'content' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'is_delta' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'hash_before' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 50
            ],
            'hash_after' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false,
                'length' => 50
            ]
        ]);
    }
    public function writerNotice(): void
    {
        $this->db->createTable('xlas_et_writer_notice', [
            'id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'essay_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'note_no' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'note_text' => [
                'type' => ilDBConstants::T_TEXT,
                'notnull' => false
            ],
            'last_change' => [
                'type' => ilDBConstants::T_TIMESTAMP,
                'notnull' => false
            ]
        ]);
    }
    public function writerPrefs(): void
    {
        $this->db->createTable('xlas_et_writer_prefs', [
            'writer_id' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'instructions_zoom' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'editor_zoom' => [
                'type' => ilDBConstants::T_FLOAT,
                'notnull' => true
            ],
            'word_count_enabled' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ],
            'word_count_characters' => [
                'type' => ilDBConstants::T_INTEGER,
                'notnull' => true,
                'length' => 4
            ]
        ]);
    }
}
