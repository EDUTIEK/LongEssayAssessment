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
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;
use ILIAS\DI\Container;
use ilObject;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use ILIAS\Plugin\LongEssayAssessment\Assessment\DisabledGroup\DisabledGroup;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use ILIAS\Plugin\LongEssayAssessment\DisabledGroupGUI;

class ToolProvider extends AbstractDynamicToolProvider
{
    final public const NAME = 'xlas_tool';
    final public const GUI_CLASS = 'gui_class';

    private readonly string $gui_class;
    private readonly int $ref_id;
    private readonly int $obj_id;
    private readonly RequestVariables $get;
    private readonly ilLongEssayAssessmentPlugin $plugin;
    private readonly AssessmentApi $assessment_api;
    private readonly Permissions $permissions;
    private readonly DisabledGroup $disabled_group;

    public function __construct(Container $dic)
    {
        parent::__construct($dic);
        $this->get = new RequestVariables($dic->http()->wrapper()->query(), $dic->refinery());
        $this->plugin = $dic['component.factory']->getPlugin('xlas');
        $this->ref_id = $this->get->integer('ref_id');
        $this->obj_id = ilObject::_lookupObjId($this->ref_id);
        $this->assessment_api = $this->plugin->dic()->assessment($this->obj_id, $this->dic->user()->getId());
        $this->permissions = $this->assessment_api->permissions($this->ref_id);
        $this->disabled_group = new DisabledGroup(
            $dic->ui()->factory(),
            $this->assessment_api,
            $dic->ui()->mainTemplate(),
            $this->plugin->txt(...),
            fn($x) => $x,
            $this->permissions,
        );
    }

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

        $this->dic->ctrl()->setParameterByClass($this->gui_class, 'ref_id', (string) $this->ref_id);

        $tab = fn(string $lang_var, $content) => $this->factory
            ->tool($this->identification_provider->contextAwareIdentifier('xlas_' . $lang_var))
            ->withTitle($this->plugin->txt($lang_var))
            ->withContent($this->dic->ui()->factory()->legacy($this->dic->ui()->renderer()->render($content)));

        $tabs = [];
        if ($this->isMultiTask()) {
            $tabs[] = $tab('tab_task', $this->multiTaskContent());
        }

        if ($this->permissions->canEditTemplates()) {
            $tabs[] = $tab('disabled_group_tool_tab', $this->disabledGroupContent());
        }

        return $tabs;
    }

    private function disabledGroupContent(): array
    {
        $this->dic->ctrl()->setParameterByClass(DisabledGroupGUI::class, 'return_url', urlencode((string) $this->dic->http()->request()->getUri()));
        return [
            // Comment in to use toggle buttons:
            // $this->disabled_group->toggles($this->dic->ctrl()->getLinkTargetByClass([\ilObjLongEssayAssessmentGUI::class, DisabledGroupGUI::class], 'saveGroups')),

            // Comment out to disable form:
            $this->disabled_group->form($this->dic->ctrl()->getLinkTargetByClass([\ilObjLongEssayAssessmentGUI::class, DisabledGroupGUI::class], 'saveGroups')),

            $this->disabled_group->toggleButton(),
        ];
    }

    private function multiTaskContent(): array
    {
        $glyph = $this->dic->ui()->factory()->symbol()->glyph();
        $link = $this->dic->ui()->factory()->link()->bulky(...);

        $all = $this->plugin->dic()->task(
            $this->obj_id,
            $this->dic->user()->getId()
        )->manager()->all();

        $links = array_map(
            fn(TaskInfo $t) => $link($glyph->link(), $t->getTitle(), $this->uriToTask($t)),
            $all
        );
        $add_button = $link($glyph->add(), $this->dic->language()->txt('add'), $this->uriToClass(InstructionSettingsGUI::class, 'create'));

        return array_merge($links, [$add_button]);
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

    private function isMultiTask(): bool
    {
        return $this->assessment_api->orgaSettings()->get()->getMultiTasks();
    }
}
