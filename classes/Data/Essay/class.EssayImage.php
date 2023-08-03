<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Data\Essay;

use ILIAS\Plugin\LongEssayAssessment\Data\RecordData;

/**
 * @author Fred Neumann <neumann@ilias.de>
 */
class EssayImage extends RecordData
{
    protected const tableName = 'xlas_essay_image';
    protected const hasSequence = true;
    protected const keyTypes = [
        'id' => 'integer',
    ];
    protected const otherTypes = [
        'essay_id' => 'integer',
        'page_no' => 'integer',
        'file_id' => 'text',
        'width' => 'integer',
        'height' => 'integer'
    ];

    protected int $id = 0;
    protected int $essay_id = 0;
    protected int $page_no;
    protected string $file_id;
    protected int $width;
    protected int $height;
    
    
    public function __construct(
        int $id,
        int $essay_id,
        int $page_no,
        string $file_id,
        int $width,
        int $height
    ) {
        $this->id = $id;
        $this->essay_id = $essay_id;
        $this->page_no = $page_no;
        $this->file_id = $file_id;
        $this->width = $width;
        $this->height = $height;
    }
    
    public static function model()
    {
        return new self(0,0,0,'',0,0);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEssayId(): int
    {
        return $this->essay_id;
    }

    /**
     * @return int
     */
    public function getPageNo(): int
    {
        return $this->page_no;
    }

    /**
     * @return string
     */
    public function getFileId(): string
    {
        return $this->file_id;
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }

}