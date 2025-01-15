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

#[Table(name: 'xlas_et_essay_image')]
class EssayImage extends \Edutiek\AssessmentService\EssayTask\Data\EssayImage
{
    #[Key]
    private int $id;
    private int $essay_id;
    private int $page_no;
    private int $width;
    private int $height;
    private string $mime;
    private ?int $thumb_width;
    private ?int $thumb_height;
    private ?string $thumb_mime;
    private string $file_id;
    private ?string $thumb_id;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    public function getEssayId(): int
    {
        return $this->essay_id;
    }
    public function setEssayId(int $essay_id): void
    {
        $this->essay_id = $essay_id;
    }
    public function getPageNo(): int
    {
        return $this->page_no;
    }
    public function setPageNo(int $page_no): void
    {
        $this->page_no = $page_no;
    }
    public function getWidth(): int
    {
        return $this->width;
    }
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }
    public function getHeight(): int
    {
        return $this->height;
    }
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }
    public function getMime(): string
    {
        return $this->mime;
    }
    public function setMime(string $mime): void
    {
        $this->mime = $mime;
    }
    public function getThumbWidth(): ?int
    {
        return $this->thumb_width;
    }
    public function setThumbWidth(?int $thumb_width): void
    {
        $this->thumb_width = $thumb_width;
    }
    public function getThumbHeight(): ?int
    {
        return $this->thumb_height;
    }
    public function setThumbHeight(?int $thumb_height): void
    {
        $this->thumb_height = $thumb_height;
    }
    public function getThumbMime(): ?string
    {
        return $this->thumb_mime;
    }
    public function setThumbMime(?string $thumb_mime): void
    {
        $this->thumb_mime = $thumb_mime;
    }
    public function getFileId(): string
    {
        return $this->file_id;
    }
    public function setFileId(string $file_id): void
    {
        $this->file_id = $file_id;
    }
    public function getThumbId(): ?string
    {
        return $this->thumb_id;
    }
    public function setThumbId(?string $thumb_id): void
    {
        $this->thumb_id = $thumb_id;
    }
}
