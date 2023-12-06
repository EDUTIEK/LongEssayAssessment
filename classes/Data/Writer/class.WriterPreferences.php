<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Writer;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * Writer Preferences
 *
 * @author Fred Neumann <neumann@ilias.de>
 */
class WriterPreferences extends RecordData
{

	protected const tableName = 'xlas_writer_prefs';
	protected const hasSequence = false;
	protected const keyTypes = [
		'writer_id' => 'integer',
	];
	protected const otherTypes = [
		'instructions_zoom' => 'float',
		'editor_zoom' => 'float',
	];

    protected int $writer_id = 0;
    protected float $instructions_zoom = 1;            
    protected float $editor_zoom =  1;              
    
	public function __construct(int $writer_id)
	{
		$this->writer_id = $writer_id;
	}

	public static function model(): WriterPreferences
	{
		return new self(0);
	}

    /**
     * Get the writer id
     */
    public function getWriterId(): int
    {
        return $this->writer_id;
    }

    
    /**
     * Set the writer id
     */
    public function setWriterId(int $writer_id): WriterPreferences
    {
        $this->writer_id = $writer_id;
        return $this;
    }

    /**
     * Get the zoom level of the instructions
     */
    public function getInstructionsZoom(): float
    {
        return $this->instructions_zoom;
    }

    /**
     * Set the zoom level of the instructions
     */
    public function setInstructionsZoom(float $instructions_zoom): WriterPreferences
    {
        $this->instructions_zoom = $instructions_zoom;
        return $this;
    }

    /**
     * Set the zoom level of the editor
     */
    public function getEditorZoom(): float
    {
        return $this->editor_zoom;
    }

    /**
     * Get the zoom level of the editor
     */
    public function setEditorZoom(float $editor_zoom): WriterPreferences
    {
        $this->editor_zoom = $editor_zoom;
        return $this;
    }

}