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

namespace ILIAS\Plugin\LongEssayAssessment\UI\Viewer;

use ILIAS\UI\Implementation\Render\AbstractComponentRenderer;
use ILIAS\UI\Renderer;
use ILIAS\UI\Component\Component;
use LogicException;
use ILIAS\UI\Implementation\Render\ResourceRegistry;
use ilLongEssayAssessmentPlugin;

class ViewerRenderer extends AbstractComponentRenderer
{
    protected function getComponentInterfaceName(): array
    {
        return [PdfViewer::class, AudioPlayer::class, VideoPlayer::class, ImageViewer::class, ComponentSwitch::class, HtmlContent::class];
    }

    public function registerResources(ResourceRegistry $registry): void
    {
        $registry->register(\ilLongEssayAssessmentPlugin::assetPath() . '/js/xlas.min.js');
        $registry->register(\ilLongEssayAssessmentPlugin::assetPath() . '/css/content.css');
    }

    public function render(Component $component, Renderer $default_renderer): string
    {
        return match (true) {
            $component instanceof PdfViewer => $this->renderPdfViewer($component, $default_renderer),
            $component instanceof AudioPlayer => $this->renderMime('audio_player', $component),
            $component instanceof VideoPlayer => $this->renderMime('video_player', $component),
            $component instanceof ImageViewer => $this->renderMime('image_viewer', $component),
            $component instanceof HtmlContent => $this->renderHtmlContent($component),
            $component instanceof ComponentSwitch => $this->renderComponentSwitch($component, $default_renderer),
            default => throw new LogicException("Cannot render '" . get_class($component) . "'"),
        };
    }

    public function renderPdfViewer(PdfViewer $component, Renderer $default_renderer): string
    {
        $url = $component->getUrl();
        if (empty(parse_url($url, PHP_URL_HOST))) {
            $url = ILIAS_HTTP_PATH . '/' . ltrim($url, '/');
        }

        $url= ilLongEssayAssessmentPlugin::assetPath() . "/pdf-viewer/pdfjs-dist/web/viewer.html?file=" . urlencode($url);

        $tpl = $this->getTemplate("tpl.pdf_viewer.html", true, true);
        $tpl->setVariable('URL', $url);
        if ($component->getCaption() !== null) {
            $tpl->setVariable('CAPTION', $component->getCaption());
        }
        return $tpl->get();
    }

    private function renderMime(string $template, Media $media): string
    {
        $tpl = $this->getTemplate('tpl.' . $template . '.html', true, true);
        $tpl->setVariable('URL', $media->getUrl());
        $tpl->setVariable('MIME_TYPE', $media->getMimeType());
        if ($media->getCaption() !== null) {
            $tpl->setVariable('CAPTION', $media->getCaption());
        }
        return $tpl->get();
    }

    public function renderComponentSwitch(ComponentSwitch $component, Renderer $default_renderer): string
    {
        $tpl = $this->getTemplate("tpl.component_switch.html", true, true);
        $switch = $component->getSwitchSignal();

        /** @var ComponentSwitch $component */
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

    public function renderHtmlContent(HtmlContent $component): string
    {
        $html = ilLongEssayAssessmentPlugin::getInstance()->dic()->system()->htmlProcessing()->secureContent($component->getHtml());

        $tpl = $this->getTemplate("tpl.html_content.html", true, true);
        $tpl->setVariable('HTML', $html);
        $tpl->setVariable('HEADLINE_CLASS', $component->getScheme()->class());
        if ($component->getPurpose() == HtmlContent::FOR_MESSAGE) {
            $tpl->setVariable('PURPOSE_STYLE', 'font-family: sans-serif;');
        }

        return $tpl->get();
    }

    protected function getTemplatePath(string $name): string
    {
        return 'Viewer/' . $name;
    }

}
