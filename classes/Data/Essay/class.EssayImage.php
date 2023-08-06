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
        'mime' => 'text',
        'width' => 'integer',
        'height' => 'integer',
        'thumb_id' => 'text',
        'thumb_mime' => 'text',
        'thumb_width' => 'integer',
        'thumb_height' => 'integer'

    ];

    protected int $id = 0;
    protected int $essay_id = 0;
    protected int $page_no;
    protected string $file_id;
    protected string $mime;
    protected int $width;
    protected int $height;
    protected ?string $thumb_id;
    protected ?string $thumb_mime;
    protected ?int $thumb_width;
    protected ?int $thumb_height;


    public function __construct(
        int $id,
        int $essay_id,
        int $page_no,
        string $file_id,
        string $mime,
        int $width,
        int $height,
        ?string $thumb_id,
        ?string $thumb_mime,
        ?int $thumb_width,
        ?int $thumb_height
    ) {
        $this->id = $id;
        $this->essay_id = $essay_id;
        $this->page_no = $page_no;
        $this->file_id = $file_id;
        $this->mime = $mime;
        $this->width = $width;
        $this->height = $height;
        $this->thumb_id = $thumb_id;
        $this->thumb_mime = $thumb_mime;
        $this->thumb_width = $thumb_width;
        $this->thumb_height = $thumb_height;
    }
    
    public static function model()
    {
        return new self(0,0,0,'','', 0,0, 
        null, null, null, null);
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
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
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


    /**
     * @return string|null
     */
    public function getThumbId(): ?string
    {
        return $this->thumb_id;
    }

    /**
     * @return string|null
     */
    public function getThumbMime(): ?string
    {
        return $this->thumb_mime;
    }

    /**
     * @return int|null
     */
    public function getThumbWidth(): ?int
    {
        return $this->thumb_width;
    }

    /**
     * @return int|null
     */
    public function getThumbHeight(): ?int
    {
        return $this->thumb_heigth;
    }


}