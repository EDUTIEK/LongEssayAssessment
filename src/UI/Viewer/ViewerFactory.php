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

class ViewerFactory
{
    public const VIEWERS = [PdfViewer::class, AudioPlayer::class, VideoPlayer::class, ImageViewer::class];

    public function pdf(string $url, ?string $caption = null): PdfViewer
    {
        return new PdfViewer($url, 'application/pdf', $caption);
    }

    public function audio(string $url, string $mime_type, ?string $caption = null): AudioPlayer
    {
        return new AudioPlayer($url, $mime_type, $caption);
    }

    public function video(string $url, string $mime_type, ?string $caption = null): VideoPlayer
    {
        return new VideoPlayer($url, $mime_type, $caption);
    }

    public function image(string $url, string $mime_type, ?string $caption = null): ImageViewer
    {
        return new ImageViewer($url, $mime_type, $caption);
    }

    public function fromMimeType(string $url, string $mime_type, ?string $caption = null): ?Media
    {
        foreach (self::VIEWERS as $viewer) {
            if (in_array($mime_type, $viewer::supportedMimeTypes())) {
                return new $viewer($url, $mime_type, $caption);
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function supportedMimeTypes(): array
    {
        return array_merge(...array_map(fn($s) => $s::supportedMimeTypes(), self::VIEWERS));
    }
}
