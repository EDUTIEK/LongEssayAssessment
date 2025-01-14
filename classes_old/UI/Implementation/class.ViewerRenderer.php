<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Component;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\PdfViewer;
use LogicException;

class ViewerRenderer extends AbstractComponentRenderer
{

    protected function getComponentInterfaceName(): array
    {
        return [PdfViewer::class];
    }

    public function render(Component $component, Renderer $default_renderer): string
    {
        $this->checkComponent($component);
        switch (true) {
            case ($component instanceof PdfViewer):
                return $this->renderPdfViewer($component, $default_renderer);
            default:
                throw new LogicException("Cannot render '" . get_class($component) . "'");
        }
    }

    public function renderPdfViewer(PdfViewer $component, Renderer $default_renderer): string
    {
        $tpl = $this->getTemplate("tpl.pdf_viewer.html", true, true);
        $tpl->setVariable('URL', $component->getUrl());
        if ($component->getCaption() !== null) {
            $tpl->setVariable('CAPTION', $component->getCaption());
        }
        return $tpl->get();
    }

    protected function getTemplatePath($name) : string
    {
        return __DIR__ . '/../../../templates/Viewer/' . $name;
    }

}