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
    final public const WITH_TASK_SELECTION = 'xlas_task_selection';
    final public const WITH_FIXATIONS = 'xlas_fixations';
    final public const GUI_CLASS = 'gui_class';

    private readonly string $gui_class;
    private readonly int $ref_id;
    private readonly int $obj_id;
    private readonly RequestVariables $get;
    private readonly ilLongEssayAssessmentPlugin $plugin;
    private readonly AssessmentApi $assessment_api;
    private readonly Permissions $permissions;
    private readonly DisabledGroup $disabled_group;
    private \ILIAS\UI\Factory $ui_factory;

    public function __construct(Container $dic)
    {
        parent::__construct($dic);
        $this->get = new RequestVariables($dic->http()->wrapper()->query(), $dic->refinery());
        $this->plugin = $dic['component.factory']->getPlugin('xlas');
        $this->ref_id = $this->get->integer('ref_id');
        $this->obj_id = ilObject::_lookupObjId($this->ref_id);
        $this->assessment_api = $this->plugin->dic()->assessment($this->obj_id, $this->dic->user()->getId());
        $this->permissions = $this->assessment_api->permissions($this->ref_id);
        $this->ui_factory = $dic->ui()->factory();
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
        if ($additional_data->exists(self::GUI_CLASS)) {
            $this->gui_class = $additional_data->get(self::GUI_CLASS);
            $this->dic->ctrl()->setParameterByClass($this->gui_class, 'ref_id', (string) $this->ref_id);
        } else {
            return [];
        }

        $tabs = [];

        if ($this->isMultiTask() && $additional_data->is(self::WITH_TASK_SELECTION, true)) {
            $tabs[] = $this->factory
                ->tool($this->identification_provider->contextAwareIdentifier('xlas_tab_task'))
                ->withTitle($this->plugin->txt('tools_tab_tasks'))
                ->withContent($this->dic->ui()->factory()->legacy($this->dic->ui()->renderer()->render($this->multiTaskContent())));
        }

        if ($this->permissions->canEditTemplates() && $additional_data->is(self::WITH_FIXATIONS, true)) {
            $tabs[] = $this->factory
                ->tool($this->identification_provider->contextAwareIdentifier('xlas_disabled_group_tool_tab'))
                ->withTitle($this->plugin->txt('tools_tab_fixations'))
                ->withContent($this->dic->ui()->factory()->legacy($this->dic->ui()->renderer()->render($this->disabledGroupContent())));
        }

        return $tabs;
    }

    private function disabledGroupContent(): array
    {
        $this->dic->ctrl()->setParameterByClass(DisabledGroupGUI::class, 'return_url', urlencode((string) $this->dic->http()->request()->getUri()));

        $fixing_content = [];
        foreach ($this->disabled_group->supportedTabs() as $tab) {
            $fixing_content = array_merge($fixing_content, [
                $this->ui_factory->divider()->horizontal(),
                $this->ui_factory->legacy('<strong>' . $this->plugin->txt($tab) . '</strong>'),
                ...$this->disabled_group->toggles(
                    $tab,
                    $this->dic->ctrl()->getLinkTargetByClass([\ilObjLongEssayAssessmentGUI::class, DisabledGroupGUI::class], 'saveGroups')
                )
            ]);
        }

        return [
            $this->ui_factory->panel()->standard('Anzeige', [
                        $this->ui_factory->legacy('<p class="small">' . $this->plugin->txt('disabled_group_toggle_info') . '</p>'),
                        $this->disabled_group->toggleButton()]),

             $this->ui_factory->panel()->standard('Festlegungen', [
                 $this->ui_factory->legacy('<p class="small">' . $this->plugin->txt('disabled_group_info') . '</p>'),
                 ... $fixing_content
             ])
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

        return $links;
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
