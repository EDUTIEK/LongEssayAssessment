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

namespace ILIAS\Plugin\LongEssayAssessment;

use ILIAS\GlobalScreen\Scope\Tool\Provider\AbstractDynamicToolProvider;
use ILIAS\GlobalScreen\ScreenContext\Stack\ContextCollection;
use ILIAS\GlobalScreen\ScreenContext\Stack\CalledContexts;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use ILIAS\Data\URI;
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;
use ILIAS\DI\Container;
use ilObject;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use ilObjLongEssayAssessment;

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
    private readonly BaseObjectData $object;
    private readonly Permissions $permissions;
    private readonly FixationGUI $fixation_gui;

    public function __construct(Container $dic)
    {
        parent::__construct($dic);
        $this->get = new RequestVariables($dic->http()->wrapper()->query(), $dic->refinery());
        $this->ref_id = $this->get->integer('ref_id');
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->object = new ilObjLongEssayAssessment($this->ref_id);
        $this->permissions = $this->plugin->dic()->assessment(
            $this->object->getAssId(),
            $this->dic->user()->getId()
        )->permissions($this->object->getContextId());
        $this->fixation_gui = new FixationGUI($this->object);
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

        if ($this->object->getMultiTasks() && $additional_data->is(self::WITH_TASK_SELECTION, true)) {
            $tabs[] = $this->factory
                ->tool($this->identification_provider->contextAwareIdentifier('xlas_tab_task'))
                ->withTitle($this->plugin->txt('tools_tab_tasks'))
                ->withContent($this->dic->ui()->factory()->legacy($this->dic->ui()->renderer()->render($this->multiTaskContent())));
        }

        if ($this->permissions->canEditTemplates() && $additional_data->is(self::WITH_FIXATIONS, true)) {
            $tabs[] = $this->factory
                ->tool($this->identification_provider->contextAwareIdentifier('xlas_disabled_group_tool_tab'))
                ->withTitle($this->plugin->txt('tools_tab_template'))
                ->withContent($this->dic->ui()->factory()->legacy($this->dic->ui()->renderer()->render(
                    $this->fixation_gui->toolsContent()
                )));
        }

        return $tabs;
    }

    private function multiTaskContent(): array
    {
        $glyph = $this->dic->ui()->factory()->symbol()->glyph();
        $link = $this->dic->ui()->factory()->link()->bulky(...);

        $all = $this->plugin->dic()->task(
            $this->object->getAssId(),
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
}
