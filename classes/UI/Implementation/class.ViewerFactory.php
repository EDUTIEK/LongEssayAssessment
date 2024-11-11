<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\PdfViewer as PdfViewerInterface;

class ViewerFactory implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\ViewerFactory
{
    public function pdf(string $url, ?string $caption = null): PdfViewerInterface
    {
        return new PdfViewer($url, $caption);
    }
}
