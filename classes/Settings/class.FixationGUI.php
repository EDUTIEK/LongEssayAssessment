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

use Edutiek\AssessmentService\Assessment\Data\DisabledGroup as DisabledGroupEntity;
use Exception;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Factory as UIFactory;
use ilLongEssayAssessmentPlugin;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use Edutiek\AssessmentService\Assessment\DisabledGroup\FullService as DisabledGroupService;
use Edutiek\AssessmentService\Assessment\OrgaSettings\FullService as OrgaSettingsService;
use ILIAS\Plugin\LongEssayAssessment\Settings\OrgaSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;
use ilObjLongEssayAssessmentGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Container\Bindable;

/**
 * @ilCtrl_IsCalledBy ILIAS\Plugin\LongEssayAssessment\FixationGUI: ilObjLongEssayAssessmentGUI
 */
class FixationGUI
{
    private \ilCtrlInterface $ctrl;
    private \ILIAS\HTTP\Services $http;
    private RequestVariables $get;
    private ilLongEssayAssessmentPlugin $plugin;
    private UIFactory $ui_factory;
    private SignalGeneratorInterface $signal_generator;
    private DisabledGroupService $service;
    private OrgaSettingsService $settings;
    private $multi_tasks;

    /**
     * GUI tabs that have disabled groups
     * - Key is the id (and language variable) of the tab
     * - Value is a list of keys of settings groups that are shown on the tab
     */
    private const TABS = [
        'tab_orga_settings' => [
            'orga_object',
            'orga_type',
            'orga_info',
            'orga_writing',
            'orga_correction',
            'orga_review'
        ],
        'tab_instructions_settings' => [
            'task_title',
            'instructions_text',
            'instructions_pdf'
        ],
        'tab_solution_settings' => [
            'solution_text',
            'solution_pdf'
        ],
        'tab_resources' => [
            'resources'
        ],
        'tab_technical_settings' => [
            'tech_editor',
            'tech_processing'
        ],
        'tab_correction_settings' => [
            'correctors',
            'correction_settings',
            'rating_settings',
            'correction_functions',
        ],
        'tab_criteria' => [
          'criteria_settings',
          'criteria_list'
        ],
        'tab_grades' => [
            'grade_levels'
        ],
        'tab_documentation_settings' => [
            'pdf_config',
            'docu_settings'
        ],
        'tab_notifications' => [
            'notification_settings'
        ]
    ];

    /**
     * Settings groups that can be fixed and disabled
     * - Key is the name of the settings group that is stored
     * - Value is an array of form input names that belong to the group
     */
    private const GROUPS = [
        // tab_orga_settings
        'orga_object' => ['object'],
        'orga_type' => ['type'],
        'orga_info' => ['info'],
        'orga_writing' => ['writing'],
        'orga_correction' => ['correction'],
        'orga_review' => ['review'],
        // tab_instructions_settings
        'task_title' => ['title'],
        'instructions_text' => ['task_instructions'],
        'instructions_pdf' => ['resource_file'],
        // tab_solution_settings
        'solution_text' => ['task_solution'],
        'solution_pdf' => ['resource_file'],
        // tab_resources
        'resources' => ['resources'],
        // tab_technical_settings
        'tech_editor' => ['editor'],
        'tech_processing' => ['processing'],
        // tab_correction_settings
        'correctors' => ['correctors'],
        'correction_settings' => ['correction'],
        'correction_functions' => ['correction_functions'],
        'rating_settings' => ['rating_settings'],
        // tab_criteria
        'criteria_settings' => ['criteria_settings'],
        'criteria_list' => ['criteria_edit'],
        // tab_grades
        'grade_levels' => ['grades'],
        // tab_documentation_settings
        'docu_settings' => ['docu_settings', 'result_format', 'pdf_format', 'feedback_mode'],
        'pdf_config' => ['pdf_config'],
        // tab_notifications
        'notification_settings' => ['notification_settings']
    ];

    /**
     * Language variables
     * - Key is the name of the settings group
     * - Value is the language variable
     */
    private const LANG_VARS = [
        // tab_orga_settings
        'orga_object' => 'object_settings',
        'orga_type' => 'type_settings',
        'orga_info' => 'info_settings',
        'orga_writing' => 'writing_organisation',
        'orga_correction' => 'correction_organisation',
        'orga_review' => 'review_organisation',
        // tab_instructions_settings
        'task_title' => 'title',
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

    private bool $can_edit = false;
    private UI\Factory $plugin_ui_factory;

    private static $instances = [];

    public static function getInstance(int $ass_id, int $context_id)
    {
        return self::$instances[$ass_id][$context_id] ?? new self($ass_id, $context_id);
    }

    public function __construct(
        int $ass_id,
        int $context_id
    ) {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->ui_factory = $DIC->ui()->factory();
        $this->signal_generator = $DIC['ui.signal_generator'];

        $this->get = new RequestVariables($this->http->wrapper()->query(), $DIC->refinery());

        $main_tpl = $DIC->ui()->mainTemplate();

        $main_tpl->addJavaScript(ilLongEssayAssessmentPlugin::assetPath() . '/js/xlas.min.js');

        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();

        $assessment_api = $this->plugin->dic()->assessment($ass_id, $DIC->user()->getId());
        $this->service = $assessment_api->disabledGroup();
        $this->settings = $assessment_api->orgaSettings();
        $this->can_edit = $assessment_api->permissions($context_id)->canEditTemplates();
        $this->plugin_ui_factory = $this->plugin->dic()->uiFactory();

        $this->multi_tasks = $this->settings->get()->getMultiTasks();
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd();
        if ($this->can_edit && in_array($cmd, ['updateTemplate', 'updateGroup'])) {
            $this->$cmd();
        }
    }

    /**
     * Update the activation of the oject as a template
     */
    private function updateTemplate(): void
    {
        $enabled = (bool) $this->get->string('enable');
        $return_url = $this->get->string('return_url') ?: $this->ctrl->getLinkTargetByClass(OrgaSettingsGUI::class, 'editSettings');
        $this->settings->save($this->settings->get()->setTemplate($enabled));
        $this->ctrl->redirectToUrl($return_url);
    }

    /**
     * Update the fixation of a settings group
     */
    private function updateGroup(): void
    {
        $group_name = (string) $this->get->string('group');
        $enabled = (bool) $this->get->string('enable');
        $return_url = $this->get->string('return_url') ?: $this->ctrl->getLinkTargetByClass(OrgaSettingsGUI::class, 'editSettings');

        if ($group_name) {
            $groups = $this->service->all();
            $groups = $enabled ? array_merge($groups, [$group_name]) : array_filter($groups, fn($g) => $g->getName() !== $group_name);
            $this->service->saveAll($groups);
        }

        $this->ctrl->redirectToUrl($return_url);
    }

    /**
     * Set the visibility of a Bindable UI Container and register to show/hide it
     */
    public function setVisibility(string $tab, string $key, Bindable $component): Bindable
    {
        $disabled_inputs = $this->disabledInputs($tab);
        $visible = $this->can_edit;

        if (in_array($key, $disabled_inputs, true)) {
            $component = $component
                ->withAdditionalOnLoadCode(
                    fn($id) => "il.Xlas.Fixation.addNode('$id', " . ($visible ? 'true' : 'false') . ")"
                );
        }

        return $component;
    }

    /**
     * Disable form inputs whose names found in the list of disabled settings
     * These inputs are disabled and get a CSS class to show/hide them
     *
     * @param FormInput[]|Group[]|Section[] $sections
     */
    public function disableBySetting(string $tab, array $sections): array
    {
        $disabled_inputs = $this->disabledInputs($tab);
        $visible = $this->can_edit;

        foreach ($sections as $key => $section) {
            if (in_array($key, $disabled_inputs, true)) {
                $sections[$key] = $section
                    ->withDisabled(true)
                    ->withAdditionalOnLoadCode(
                        fn($id) => "il.Xlas.Fixation.addNode('$id', " . ($visible ? 'true' : 'false') . ")"
                    );
            }
        }
        return $sections;
    }

    /**
     * Check if a tab has groups that are visible to the user
     */
    public function hasVisibleGroups(string $tab): bool
    {
        if ($this->can_edit) {
            return true;
        }

        if (isset(self::TABS[$tab])) {
            foreach (array_keys($this->filterGroups($tab)) as $group) {
                if (!in_array($group, $this->disabledGroups())) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Check if a form input with a name is disabled and fixed
     */
    public function isDisabled(string $tab, string $name): bool
    {
        return in_array($name, $this->disabledInputs($tab), true);
    }

    /**
     * @return Component[]
     */
    public function toolsContent(string $selected_tab = 'tab_resources'): array
    {
        $template_content = [
            $this->plugin_ui_factory->legacy('<p class="small">' . $this->plugin->txt('template_info') . '</p>'),
            $this->templateToggle(),
        ];

        $fixing_content = [
            $this->plugin_ui_factory->legacy('<p class="small">' . $this->plugin->txt('fixation_info') . '</p>'),
            $this->hideToggle()
        ];
        foreach (array_keys(self::TABS) as $tab) {
            if (empty($selected_tab) || $tab == $selected_tab) {
                $fixing_content = array_merge($fixing_content, [
                    $this->ui_factory->divider()->horizontal(),
                    $this->plugin_ui_factory->legacy('<strong>' . $this->plugin->txt($tab) . '</strong>'),
                    ...$this->fixingToggles($tab)
                ]);
            }
        }

        return [
            $this->ui_factory->panel()->standard($this->plugin->txt('template_title'), $template_content),
            $this->ui_factory->panel()->standard($this->plugin->txt('fixation_title'), $fixing_content),
        ];
    }

    /**
     * Get a UI button that toggles the visibility of all fixed inputs on the screen
     */
    private function templateToggle(): Button
    {
        $active = $this->settings->get()->getTemplate();
        $value = $active ? "0" : "1";
        $click = $this->signal_generator->create();
        $url = json_encode($this->getUrl('updateTemplate'));

        return $this->ui_factory->button()->toggle($this->plugin->txt('template_toggle'), $click, $click, $active)
            ->withAdditionalOnLoadCode(fn($id) => "$(document).on('$click', () => {il.Xlas.Fixation.enableTemplate($url, $value);})");
    }

    /**
     * Get a UI button that toggles the visibility of all fixed inputs on the screen
     */
    private function hideToggle(): Button
    {
        $click = $this->signal_generator->create();
        return $this->ui_factory->button()->toggle($this->plugin->txt('fixation_hide_toggle'), $click, $click)
            ->withAdditionalOnLoadCode(fn($id) => "$(document).on('$click', () => {il.Xlas.Fixation.toggleNodes()})");
    }

    /**
     * Get the toggle buttons for fixable settings groups shown on a tab
     * @throws Exception
     */
    private function fixingToggles(string $tab): array
    {
        $post_url = $this->getUrl('updateGroup');
        $groups = array_keys($this->filterGroups($tab));
        $disabled = $this->disabledGroups();

        $set = function ($group, $value) use ($post_url) {
            $toggle = $this->signal_generator->create();
            $toggle_event = json_encode((string) $toggle);
            $post_url = json_encode($post_url);
            $group = json_encode($group);
            $value = json_encode($value);

            return [
                $toggle,
                "\$(document).on($toggle_event, () => {il.Xlas.Fixation.updateGroup($post_url, $group, $value);});",
            ];
        };

        return array_combine($groups, array_map(
            function ($group) use ($disabled, $set) {
                [$on, $ons] = $set($group, 'yes');
                [$off, $offs] = $set($group, '');
                return $this->ui_factory->button()
                    ->toggle($this->plugin->txt(self::LANG_VARS[$group]), $on, $off, in_array($group, $disabled, true))
                    ->withAdditionalOnLoadCode(fn($id) => $ons . $offs);
            },
            $groups
        ));
    }

    /**
     * Get the URL for executing a command
     * This adds a return url for redirection after the command is executed
     */
    private function getUrl(string $cmd): string
    {
        $this->ctrl->setParameterByClass(self::class, 'return_url', urlencode((string) $this->http->request()->getUri()));
        return $this->ctrl->getLinkTargetByClass([ilObjLongEssayAssessmentGUI::class, self::class], $cmd);
    }

    /**
     * Get the defined groups filtered by tab
     */
    private function filterGroups(string $tab): array
    {
        $omit = [];
        if ($tab == 'tab_instructions_settings' && !$this->multi_tasks) {
            $omit[] = 'task_title';
        }

        return array_filter(
            self::GROUPS,
            fn($key) => in_array($key, self::TABS[$tab] ?? []) && !in_array($key, $omit),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get the names of all settings groups that are fixed
     * @return string[]
     */
    private function disabledGroups(): array
    {
        return array_map(
            fn(DisabledGroupEntity $g) => $g->getName(),
            $this->service->all()
        );
    }

    /**
     * Get the names of all form inputs that belong to disabled groups of settings
     * @return string[]
     */
    private function disabledInputs(string $tab): array
    {
        return array_merge(...array_map(
            fn($k) => $this->filterGroups($tab)[$k] ?? [],
            $this->disabledGroups()
        ));
    }
}
