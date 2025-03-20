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

namespace ILIAS\Plugin\LongEssayAssessment\Provider;

use ILIAS\GlobalScreen\Scope\Tool\Provider\AbstractDynamicToolProvider;
use ILIAS\GlobalScreen\ScreenContext\Stack\ContextCollection;
use ILIAS\GlobalScreen\ScreenContext\Stack\CalledContexts;

class ToolProvider extends AbstractDynamicToolProvider
{
    final public const NAME = 'xlas_tool';

    public function isInterestedInContexts(): ContextCollection
    {
        return $this->context_collection->repository();
    }

    public function getToolsForContextStack(CalledContexts $called_contexts): array
    {
        if (!$called_contexts->getLast()->getAdditionalData()->is(self::NAME, true)) {
            return [];
        }

        $plugin = $this->dic['component.factory']->getPlugin('xlas');
        $all = $plugin->dic()->task(
            $this->dic->http()->wrapper()->query()->retrieve('ref_id', $this->dic->refinery()->kindlyTo()->int()),
            $this->dic->user()->getId()
        )->manager()->all();

        $tool = $this->factory
            ->tool($this->identification_provider->contextAwareIdentifier('xlas_tool'))
            ->withTitle('Huhu')
            ->withContentWrapper(fn () => $this->dic->ui()->factory()->legacy(
                join('<br/>', array_map(fn($x) => (string) $x->getId(), $all))
            ));

        return [$tool];
    }
}
