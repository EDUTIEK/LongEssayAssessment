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

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\DisabledGroup;

use Closure;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\Assessment\Data\DisabledGroup as DisabledGroupEntity;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Exception;
use ilGlobalTemplateInterface;
use ILIAS\Plugin\LongEssayAssessment\Settings\CorrectionSettingsGUI;
use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\Input\Container\Form\FormInput;
use ILIAS\UI\Component\Input\Field\Group;
use ILIAS\UI\Component\Input\Field\Section;
use ILIAS\UI\Factory as UIFactory;

class DisabledGroup
{
    /**
     * GUI tabs that have disabled groups
     * - Key is the id (and language variable) of the tab
     * - Value is a list of settings groups that are shown on the tab
     */
    private const TABS = [
        'tab_correction_settings' => [
            'correctors',
            'rating_settings',
            'correction_functions',
        ],
        'tab_grades' => ['grade_levels']
    ];

    /**
     * Settings groups that can be fixed and disabled
     * - Key is the name of the settings group
     * - Value is an array of form input names that belong to the group
     */
    private const GROUPS = [
        'correctors' => ['correctors'],
        'correction_functions' => ['correction_functions'],
        'rating_settings' => ['rating_settings'],
        'grade_levels' => ['grades'],
    ];

    /**
     * Lang
     */
    private const LANG_VARS = [
        'correctors' => 'correctors_per_writer',
        'correction_functions' => 'correction_functions',
        'rating_settings' => 'rating_settings',
        'grade_levels' => 'grade_levels',
    ];

    /**
     * @param Closure(string): string $txt
     * @param Closure(Form, callable(array): void): Form $with_form
     */
    public function __construct(
        private readonly UIFactory $ui_factory,
        private readonly AssessmentApi $assessment_api,
        private readonly ilGlobalTemplateInterface $main_tpl,
        private readonly Closure $txt,
        private readonly Closure $with_form,
        private readonly Permissions $perms,
    ) {
    }

    /**
     * Disable form inputs whose names found in the list of disabled settings
     * These inputs are disabled and get a CSS class to show/hide them
     *
     * @param FormInput[]|Group[]|Section[] $sections
     * @return array
     */
    public function disableBySetting(string $tab, array $sections): array
    {
        $disabled = $this->disabledInputs($tab);

        foreach ($sections as $key => $section) {
            if (in_array($key, $disabled, true)) {
                $sections[$key] = $section->withDisabled(true)->withAdditionalOnLoadCode(
                    fn($id) => "il.EDUTIEK.disableInput($id)"
                );
            }
        }

        return $sections;
    }

    /**
     * Check if a form input with a name is disabled and fixed
     */
    public function isDisabled(string $tab, string $name): bool
    {
        return in_array($name, $this->disabledInputs($tab), true);
    }

    /**
     * Get a UI button that toggles the visibility of all fixed inputs on the screen
     */
    public function toggleButton(): Button
    {
        global $DIC;
        $this->main_tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/templates/default/DisabledGroup/disabled-group.js');
        $click = $DIC['ui.signal_generator']->create();
        return $this->ui_factory->button()->toggle(($this->txt)(''), $click, $click)
            ->withAdditionalOnLoadCode(fn($id) => "$(document).on('$click', il.EDUTIEK.toggleDisabledInputs)");
    }

    /**
     * Get the toggle buttons for fixable settings groups shown on a tab
     * @throws Exception
     */
    public function toggles(string $tab, string $post_url): array
    {
        if (!$this->perms->canEditTemplates()) {
            throw new Exception('permission_denied');
        }

        $groups = array_values(self::TABS[$tab] ?? []);
        $disabled = $this->disabledGroups();

        $set = function ($group, $value) use ($post_url) {
            global $DIC;
            $toggle = $DIC['ui.signal_generator']->create();
            $toggle_event = json_encode((string) $toggle);
            $post_url = json_encode($post_url);
            $group = json_encode($group);
            $value = json_encode($value);

            return [
                $toggle,
                "\$(document).on($toggle_event, () => {il.EDUTIEK.updateGroup($post_url, $group, $value);});",
            ];
        };

        return array_combine($groups, array_map(
            function ($group) use ($disabled, $set) {
                [$on, $ons] = $set($group, 'yes');
                [$off, $offs] = $set($group, '');
                return $this->ui_factory->button()
                    ->toggle(($this->txt)(self::LANG_VARS[$group]), $on, $off, in_array($group, $disabled, true))
                    ->withAdditionalOnLoadCode(fn($id) => $ons . $offs);
            },
            $groups
        ));
    }

    public function saveModal(): void
    {
        global $DIC;
        $query = new \ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables($DIC->http()->wrapper()->query(), $DIC->refinery());
        $group_name = $query->string('group');
        $enabled = $query->string('enable');
        if ($group_name) {
            $groups = $this->assessment_api->disabledGroup()->all();
            $groups = $enabled ? array_merge($groups, [$group_name]) : array_filter($groups, fn($g) => $g->getName() !== $group_name);
            $this->assessment_api->disabledGroup()->saveAll($groups);
        }
    }

    /**
     * Get the ids of the GUI tabs that support disabled settings
     */
    public function supportedTabs(): array
    {
        return array_keys(self::TABS);
    }

    /**
     * Get the defined groups filtered by tab
     */
    private function filterGroups(string $tab): array
    {
        return array_filter(
            self::GROUPS,
            fn($key) => in_array($key, self::TABS[$tab] ?? []),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Get the names of all settings groups that are fixed an hidden
     * @return string[]
     */
    private function disabledGroups(): array
    {
        return array_map(
            fn(DisabledGroupEntity $g) => $g->getName(),
            $this->assessment_api->disabledGroup()->all()
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
