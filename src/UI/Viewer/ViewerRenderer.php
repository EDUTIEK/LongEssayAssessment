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

class ViewerRenderer extends AbstractComponentRenderer
{
    protected function getComponentInterfaceName(): array
    {
        return [PdfViewer::class, AudioPlayer::class, VideoPlayer::class, ImageViewer::class, ComponentSwitch::class];
    }

    public function render(Component $component, Renderer $default_renderer): string
    {
        return match (true) {
            $component instanceof PdfViewer => $this->renderPdfViewer($component, $default_renderer),
            $component instanceof AudioPlayer => $this->renderMime('audio_player', $component),
            $component instanceof VideoPlayer => $this->renderMime('video_player', $component),
            $component instanceof ImageViewer => $this->renderMime('image_viewer', $component),
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

        $component = $component->withOnLoadCode(function ($id) use ($url) {
            return "il.Xlas.PdfViewer.init('$id', '$url');";
        });

        $id = $this->bindJavaScript($component);

        $tpl = $this->getTemplate("tpl.pdf_viewer.html", true, true);
        $tpl->setVariable('ID', $id);
        $tpl->setVariable('URL', $component->getUrl());
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

    protected function getTemplatePath(string $name): string
    {
        return 'Viewer/' . $name;
    }

}
