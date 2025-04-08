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
use ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use ILIAS\Data\URI;

class ToolProvider extends AbstractDynamicToolProvider
{
    final public const NAME = 'xlas_tool';
    final public const GUI_CLASS = 'gui_class';

    private string $gui_class;

    public function isInterestedInContexts(): ContextCollection
    {
        return $this->context_collection->repository();
    }

    public function getToolsForContextStack(CalledContexts $called_contexts): array
    {
        $additional_data = $called_contexts->getLast()->getAdditionalData();

        if (!$additional_data->is(self::NAME, true)) {
            return [];
        }
        $this->gui_class = $additional_data->get(self::GUI_CLASS);

        $glyph = $this->dic->ui()->factory()->symbol()->glyph();
        $icon = $glyph->link();
        $link = $this->dic->ui()->factory()->link()->bulky(...);
        $plugin = $this->dic['component.factory']->getPlugin('xlas');

        $ref_id = $this->dic->http()->wrapper()->query()->retrieve('ref_id', $this->dic->refinery()->kindlyTo()->int());
        $obj_id = \ilObject::_lookupObjId($ref_id);

        $all = $plugin->dic()->task(
            $obj_id,
            $this->dic->user()->getId()
        )->manager()->all();

        $this->dic->ctrl()->setParameterByClass($this->gui_class, 'ref_id', (string) $ref_id);

        $links = array_map(
            fn(TaskInfo $t) => $link($icon, $t->getTitle(), $this->uriToTask($t)),
            $all
        );

        $add_button = $link($glyph->add(), $this->dic->language()->txt('add'), $this->uriToClass(InstructionSettingsGUI::class, 'create'));

        $tool = $this->factory
            ->tool($this->identification_provider->contextAwareIdentifier('xlas_tool'))
            ->withTitle($plugin->txt('tab_task'))
            ->withContent($this->dic->ui()->factory()->legacy($this->dic->ui()->renderer()->render(array_merge($links, [$add_button]))));

        return [$tool];
    }

    private function uriToTask(TaskInfo $task): URI
    {
        $this->dic->ctrl()->setParameterByClass($this->gui_class, 'task_id', (string) $task->getId());
        return $this->uriToClass($this->gui_class);
    }

    private function uriToClass(string $class, string $cmd = ''): URI
    {
        return new URI(ILIAS_HTTP_PATH . '/' . $this->dic->ctrl()->getLinkTargetByClass($class, $cmd));
    }
}
