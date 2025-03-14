<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Transformation;
use ILIAS\UI\Implementation\Component\ComponentHelper;

/**
 * Implementation of the viewer for PDF files
 */
class PdfViewer implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\PdfViewer
{
    use ComponentHelper;

    private string $url;
    private ?string $caption;

    public function __construct(string $url, ?string $caption = null) {
        $this->url = $url;
        $this->caption = $caption;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }
}
