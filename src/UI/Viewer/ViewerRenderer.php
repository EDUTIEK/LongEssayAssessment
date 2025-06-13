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
        return [PdfViewer::class, AudioPlayer::class, VideoPlayer::class, ImageViewer::class];
    }

    public function render(Component $component, Renderer $default_renderer): string
    {
        return match (true) {
            $component instanceof PdfViewer => $this->renderPdfViewer($component, $default_renderer),
            $component instanceof AudioPlayer => $this->renderMime('audio_player', $component),
            $component instanceof VideoPlayer => $this->renderMime('video_player', $component),
            $component instanceof ImageViewer => $this->renderMime('image_viewer', $component),
            default => throw new LogicException("Cannot render '" . get_class($component) . "'"),
        };
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

    protected function getTemplatePath(string $name): string
    {
        return 'Viewer/' . $name;
    }

}
