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

use Edutiek\AssessmentService\Assessment\Data\DisabledGroup;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Factory as UIFactory;
use ilLongEssayAssessmentPlugin;
use Edutiek\AssessmentService\Assessment\DisabledGroup\FullService as DisabledGroupService;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaSettingsService;
use ILIAS\Plugin\LongEssayAssessment\Settings\OrgaSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;
use ilSession;

/**
 * @ilCtrl_IsCalledBy ILIAS\Plugin\LongEssayAssessment\FixationGUI: ilObjLongEssayAssessmentGUI
 */
class FixationGUI
{
    private const SESSION_REDUCED = self::class . '.reduced';

    private \ilCtrlInterface $ctrl;
    private \ILIAS\HTTP\Services $http;
    private RequestVariables $get;
    private ilLongEssayAssessmentPlugin $plugin;
    private UIFactory $ui_factory;
    private DisabledGroupService $service;
    private OrgaSettingsService $settings;
    private UI\Factory $plugin_ui_factory;

    /**
     * GUI tabs that have settings groups
     * tab id => settings group key => form inputs (or sections)
     */
    private array $tabs = [
        'tab_orga_settings' => [
            'orga_type' => ['type'],
            'orga_info' => ['info'],
            'orga_writing' => ['writing'],
            'orga_correction' => ['correction'],
            'orga_review' => ['review'],
        ],
        'tab_instructions_settings' => [
            'task_admin' => ['task_admin'],
            'instructions_text' => ['task_instructions'],
            'instructions_pdf' => ['resource_file'],
        ],
        'tab_solution_settings' => [
            'solution_text' => ['task_solution'],
            'solution_pdf' => ['resource_file'],
        ],
        'tab_resources' => [
            'resources' => ['resources'],
        ],
        'tab_technical_settings' => [
            'tech_editor' => ['editor'],
            'tech_processing' => ['processing'],
        ],
        'tab_correction_settings' => [
            'correctors' => ['correctors'],
            'correction_settings' => ['correction'],
            'rating_settings' => ['rating_settings'],
            'correction_functions' => ['correction_functions'],
        ],
        'tab_criteria' => [
            'criteria_settings' => ['criteria_settings'],
            'criteria_list' => ['criteria_edit'],
        ],
        'tab_grades' => [
            'grade_levels' => ['grades'],
        ],
        'tab_documentation_settings' => [
            'pdf_config' => ['pdf_config'],
            'docu_settings' => ['docu_settings'],
        ],
        'tab_notifications' => [
            'notification_settings' => ['notification_settings']
        ]
    ];

    /**
     * Language variables
     * - Key is the name of the settings group
     * - Value is the language variable
     */
    private array $lang_vars = [
        // tab_orga_settings
        'orga_type' => 'type_settings',
        'orga_info' => 'info_settings',
        'orga_writing' => 'writing_organisation',
        'orga_correction' => 'correction_organisation',
        'orga_review' => 'review_organisation',
        // tab_instructions_settings
        'task_admin' => 'task_admin',
        'instructions_text' => 'task_instructions_text',
        'instructions_pdf' => 'task_instructions_file',
        // tab_instructions_settings
        'solution_text' => 'task_solution_text',
        'solution_pdf' => 'task_solution_file',
        // tab_resources
        'resources' => 'tab_resources',
        // tab_technical_settings
        'tech_editor' => 'editor_settings',
        'tech_processing' => 'processing_settings',
        // tab_correction_settings
        'correctors' => 'correctors_per_writer',
        'correction_settings' => 'correction_settings',
        'correction_functions' => 'correction_functions',
        'rating_settings' => 'rating_settings',
        // tab_criteria
        'criteria_settings' => 'criteria_settings',
        'criteria_list' => 'criteria_list',
        // tab_grades
        'grade_levels' => 'grades_list',
        // tab_documentation_settings
        'docu_settings' => 'docu_settings',
        'pdf_config' => 'corrected_pdf_config',
        // tab_notifications
        'notification_settings' => 'notification_settings'
    ];

    private bool $can_edit;
    private bool $reduced;
    private $current_tab = null;

    /** @var string[] All groups that are disabled */
    private array $disabled_groups = [];

    /** @var string[] Groups of the current tab */
    private array $current_groups = [];

    /** @var string[] All disabled inputs of the current tab */
    private array $disabled_inputs = [];

    private static $instance = null;

    public static function getInstance(int $ass_id, int $context_id)
    {
        return self::$instance ??= new self($ass_id, $context_id);
    }

    protected function __construct(
        int $ass_id,
        int $context_id
    ) {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->ui_factory = $DIC->ui()->factory();

        $this->get = new RequestVariables($this->http->wrapper()->query(), $DIC->refinery());
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();
        $this->plugin_ui_factory = $this->plugin->dic()->uiFactory();

        $assessment_api = $this->plugin->dic()->assessment($ass_id, $DIC->user()->getId());
        $this->service = $assessment_api->disabledGroup();
        $this->settings = $assessment_api->orgaSettings();

        if (!$this->settings->get()->getMultiTasks()) {
            unset($this->tabs['tab_instructions_settings']['task_admin']);
        }

        $this->can_edit = $assessment_api->permissions($context_id)->canEditTemplates();
        $this->reduced = (bool) ilSession::get(self::SESSION_REDUCED);
        $this->disabled_groups = array_map(fn(DisabledGroup $g) => $g->getName(), $this->service->all());
    }

    public function setCurrentTab(string $tab)
    {
        $this->current_tab = $tab;
        $this->current_groups = array_keys($this->tabs[$tab] ?? []);
        $this->disabled_inputs = array_merge(
            ...array_map(fn($group) => $this->tabs[$tab][$group] ?? [], $this->disabled_groups)
        );
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd();
        if ($this->can_edit && in_array($cmd, ['updateTemplate', 'updateReduced', 'updateGroup'])) {
            $this->$cmd();
        }
    }

    /**
     * Update the activation of the object as a template
     */
    private function updateTemplate(): void
    {
        $settings = $this->settings->get();
        $this->settings->save($settings->setTemplate(!$settings->getTemplate()));
        $this->returnToTab();
    }

    /**
     * Update the reduced view for an admin
     */
    private function updateReduced(): void
    {
        ilSession::set(self::SESSION_REDUCED, !ilSession::get(self::SESSION_REDUCED));
        $this->returnToTab();
    }

    /**
     * Update the fixation of a settings group
     */
    private function updateGroup(): void
    {
        $name = (string) $this->get->string('group');
        if ($name) {
            $groups = [];
            foreach ($this->service->all() as $group) {
                $groups[$group->getName()] = $group;
            }

            if (isset($groups[$name])) {
                unset($groups[$name]);
            } else {
                $groups[$name] = $name;
            }

            $this->service->saveAll($groups);
        }
        $this->returnToTab();
    }

    /**
     * Get a link with the current settings tab
     */
    private function getLinkWithTab(string $cmd)
    {
        $this->ctrl->setParameter($this, 'tab_id', $this->current_tab);
        return $this->ctrl->getLinkTarget($this, $cmd);
    }

    /**
     * Return to the settings tab provided by the link
     */
    private function returnToTab()
    {
        $tab_id = $this->get->string('tab_id');
        $this->ctrl->setParameterByClass(\ilObjLongEssayAssessmentGUI::class, 'tab_id', $tab_id);
        $this->ctrl->redirectByClass(\ilObjLongEssayAssessmentGUI::class);
    }

    /**
     * Check if a tab has groups that are visible to the user
     * Called from the object GUI to set the tabs
     */
    public function hasVisibleGroups(string $tab): bool
    {
        return ($tab == 'tab_orga_settings' || $this->can_edit && !$this->reduced) || !empty(array_diff(
            array_keys($this->tabs[$tab] ?? []),
            $this->disabled_groups
        ));
    }

    /**
     * Omit or disable form sections or inputs that are fixed
     * Called from the tab's GUI, a current tab must be set
     * @param FormInput[]|Group[]|Section[] $sections
     */
    public function applyFixation(array $sections): array
    {
        $applied = [];
        foreach ($sections as $name => $section) {
            if ($this->isVisible($name)) {
                if ($this->isDisabled($name)) {
                    $section = $section->withDisabled(true);
                }
                $applied[$name] = $section;
            }
        }
        return $applied;
    }


    /**
     * Check if a form section or input with a name is disabled and fixed
     */
    public function isDisabled(string $name): bool
    {
        return in_array($name, $this->disabled_inputs);
    }

    /**
     * Check if a form section or input with a name is visible
     */
    public function isVisible(string $name): bool
    {
        return !in_array($name, $this->disabled_inputs) || $this->can_edit && !$this->reduced;
    }

    /**
     * Get the components for the ToolProvider
     * @return Component[]
     */
    public function toolsContent(): array
    {
        $template_content = [
            $this->plugin_ui_factory->legacy('<p class="small">' . $this->plugin->txt('template_info') . '</p>'),
            $this->ui_factory->button()->toggle(
                $this->plugin->txt('template_toggle'),
                $this->getLinkWithTab('updateTemplate'),
                $this->getLinkWithTab('updateTemplate'),
                $this->settings->get()->getTemplate()
            )
        ];

        $fixing_content = [
            $this->plugin_ui_factory->legacy('<p class="small">' . $this->plugin->txt('fixation_info') . '</p>'),
            $this->ui_factory->button()->toggle(
                $this->plugin->txt('fixation_hide_toggle'),
                $this->getLinkWithTab('updateReduced'),
                $this->getLinkWithTab('updateReduced'),
                (bool) ilSession::get(self::SESSION_REDUCED),
            ),
            $this->ui_factory->divider()->horizontal(),
            $this->plugin_ui_factory->legacy('<strong>' . $this->plugin->txt($this->current_tab ?? '') . '</strong>'),
        ];

        foreach ($this->current_groups as $group) {
            $this->ctrl->setParameter($this, 'group', $group);
            $fixing_content[] = $this->ui_factory->button()->toggle(
                $this->plugin->txt($this->lang_vars[$group] ?? $group),
                $this->getLinkWithTab('updateGroup'),
                $this->getLinkWithTab('updateGroup'),
                in_array($group, $this->disabled_groups)
            );
        }

        return [
            $this->ui_factory->panel()->standard($this->plugin->txt('template_title'), $template_content),
            $this->ui_factory->panel()->standard($this->plugin->txt('fixation_title'), $fixing_content),
        ];
    }
}
