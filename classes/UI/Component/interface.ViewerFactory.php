<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;


interface ViewerFactory
{
    /**
     * Embedded viewer for a PDF File
     */
    public function pdf(string $url, ?string $caption = null): PdfViewer;

}
