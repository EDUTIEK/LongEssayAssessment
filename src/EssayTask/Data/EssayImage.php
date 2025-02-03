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

use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Key;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\Attribute\Table;

#[Table(name: 'xlas_et_essay_image')]
class EssayImage extends \Edutiek\AssessmentService\EssayTask\Data\EssayImage
{
    #[Key]
    private int $id = 0;
    private int $essay_id = 0;
    private int $page_no = 0;
    private int $width = 0;
    private int $height = 0;
    private string $mime = '';
    private ?int $thumb_width = null;
    private ?int $thumb_height = null;
    private ?string $thumb_mime = null;
    private string $file_id = '';
    private ?string $thumb_id = null;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }
    public function getEssayId(): int
    {
        return $this->essay_id;
    }
    public function setEssayId(int $essay_id): self
    {
        $this->essay_id = $essay_id;
        return $this;
    }
    public function getPageNo(): int
    {
        return $this->page_no;
    }
    public function setPageNo(int $page_no): self
    {
        $this->page_no = $page_no;
        return $this;
    }
    public function getWidth(): int
    {
        return $this->width;
    }
    public function setWidth(int $width): self
    {
        $this->width = $width;
        return $this;
    }
    public function getHeight(): int
    {
        return $this->height;
    }
    public function setHeight(int $height): self
    {
        $this->height = $height;
        return $this;
    }
    public function getMime(): string
    {
        return $this->mime;
    }
    public function setMime(string $mime): self
    {
        $this->mime = $mime;
        return $this;
    }
    public function getThumbWidth(): ?int
    {
        return $this->thumb_width;
    }
    public function setThumbWidth(?int $thumb_width): self
    {
        $this->thumb_width = $thumb_width;
        return $this;
    }
    public function getThumbHeight(): ?int
    {
        return $this->thumb_height;
    }
    public function setThumbHeight(?int $thumb_height): self
    {
        $this->thumb_height = $thumb_height;
        return $this;
    }
    public function getThumbMime(): ?string
    {
        return $this->thumb_mime;
    }
    public function setThumbMime(?string $thumb_mime): self
    {
        $this->thumb_mime = $thumb_mime;
        return $this;
    }
    public function getFileId(): string
    {
        return $this->file_id;
    }
    public function setFileId(string $file_id): self
    {
        $this->file_id = $file_id;
        return $this;
    }
    public function getThumbId(): ?string
    {
        return $this->thumb_id;
    }
    public function setThumbId(?string $thumb_id): self
    {
        $this->thumb_id = $thumb_id;
        return $this;
    }
}
