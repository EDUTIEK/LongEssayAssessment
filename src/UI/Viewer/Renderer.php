<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\UI\Viewer;

/**
 * ILIAS DefaultRendererFactory resolves renderers by replacing the last
 * namespace segment with "Renderer". This alias satisfies that convention
 * when nested core components bypass PluginRenderer (ILIAS 11+).
 *
 * The problem is caused by the HeaderNesting logic of the default renderer.
 * \ILIAS\UI\Implementation\Render\DecoratedRenderer::withHeaderNesting
 * returns the inner default renderer, not the plugin renderer
 *
 * The fallback renderer is constructed with ILIAS core dependencies, so
 * plugin templates must be referenced by absolute path for ilTemplate.
 */
class Renderer extends ViewerRenderer
{
    protected function getTemplatePath(string $name): string
    {
        return dirname(__DIR__, 3) . '/templates/default/Viewer/' . $name;
    }
}
