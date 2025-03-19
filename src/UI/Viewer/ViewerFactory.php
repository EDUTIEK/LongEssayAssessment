<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Viewer;


class ViewerFactory
{
    public function pdf(string $url, ?string $caption = null): PdfViewer
    {
        return new PdfViewer($url, $caption);
    }
}
