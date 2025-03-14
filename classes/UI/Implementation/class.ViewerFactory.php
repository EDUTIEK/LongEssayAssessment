<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\PdfViewer as PdfViewerInterface;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Component\SignalGenerator;

class ViewerFactory implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\ViewerFactory
{
    public function pdf(string $url, ?string $caption = null): PdfViewerInterface
    {
        return new PdfViewer($url, $caption);
    }

    public function componentSwitch(array|Component $components_a, string $switch_label, array|Component $components_b)
    {
        return new ComponentSwitch($components_a, $components_b, $switch_label, new SignalGenerator());
    }
}
