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
        return [PdfViewer::class, ComponentSwitch::class];
    }

    public function render(Component $component, Renderer $default_renderer): string
    {
        $this->checkComponent($component);
        switch (true) {
            case ($component instanceof PdfViewer):
                return $this->renderPdfViewer($component, $default_renderer);
            case ($component instanceof ComponentSwitch):
                return $this->renderComponentSwitch($component, $default_renderer);
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

    public function renderComponentSwitch(ComponentSwitch $component, Renderer $default_renderer): string
    {
        $tpl = $this->getTemplate("tpl.component_switch.html", true, true);
        $switch = $component->getSwitchSignal();

        $component = $component->withOnLoadCode(function ($id) use ($switch) {
            $ida = $id . "_A";
            $idb = $id . "_B";
            return "$(document).on('$switch', function() { $('#$ida').toggleClass('hidden'); $('#$idb').toggleClass('hidden'); });";
        });

        $cid = $this->bindJavaScript($component);

        $switch_button = $this->getUIFactory()->button()->toggle($component->getSwitchLabel(), $switch, $switch);

        $tpl->setVariable('ID', $cid);
        $tpl->setVariable('ID_A', $cid . "_A");
        $tpl->setVariable('ID_B', $cid . "_B");
        $tpl->setVariable('COMPONENTS_A', $default_renderer->render($component->getComponentsA()));
        $tpl->setVariable('COMPONENTS_B', $default_renderer->render($component->getComponentsB()));
        $tpl->setVariable('SWITCH_BUTTON', $default_renderer->render($switch_button));

        return $tpl->get();
    }

    protected function getTemplatePath($name) : string
    {
        return __DIR__ . '/../../../templates/Viewer/' . $name;
    }

}