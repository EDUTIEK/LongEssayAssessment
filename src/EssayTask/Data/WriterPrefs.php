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

namespace ILIAS\Plugin\LongEssayAssessment\EssayTask\Data;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Data\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_writer_prefs')]
class WriterPrefs extends \Edutiek\AssessmentService\EssayTask\Data\WriterPrefs
{
    #[Key]
    private int $writer_id = 0;
    private float $instructions_zoom = 0;
    private float $editor_zoom = 0;
    private int $word_count_enabled = 0;
    private int $word_count_characters = 0;

    public function getWriterId(): int
    {
        return $this->writer_id;
    }
    public function setWriterId(int $writer_id): void
    {
        $this->writer_id = $writer_id;
    }
    public function getInstructionsZoom(): float
    {
        return $this->instructions_zoom;
    }
    public function setInstructionsZoom(float $instructions_zoom): void
    {
        $this->instructions_zoom = $instructions_zoom;
    }
    public function getEditorZoom(): float
    {
        return $this->editor_zoom;
    }
    public function setEditorZoom(float $editor_zoom): void
    {
        $this->editor_zoom = $editor_zoom;
    }
    public function getWordCountEnabled(): int
    {
        return $this->word_count_enabled;
    }
    public function setWordCountEnabled(int $word_count_enabled): void
    {
        $this->word_count_enabled = $word_count_enabled;
    }
    public function getWordCountCharacters(): int
    {
        return $this->word_count_characters;
    }
    public function setWordCountCharacters(int $word_count_characters): void
    {
        $this->word_count_characters = $word_count_characters;
    }
}
