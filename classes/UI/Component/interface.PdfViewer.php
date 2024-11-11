<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Component;

/**
 * Interface of the viewer for PDF files
 */
interface PdfViewer extends Component
{
    public function getUrl(): string;

    public function getCaption(): ?string;
}
