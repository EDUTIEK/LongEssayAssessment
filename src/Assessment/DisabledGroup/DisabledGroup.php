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

use ilGlobalTemplateInterface;
use ILIAS\UI\Factory as UIFactory;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\Assessment\Data\DisabledGroup as DisabledGroupEntity;
use ILIAS\UI\Component\Button\Button;
use Closure;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Exception;
use ILIAS\UI\Component\Input\Container\Form\Form;

class DisabledGroup
{
    public const DEFINITION = [
        'sub_task' => ['max_points'],
        'grades' => ['grades'],
    ];

    private const LANG_VARS = [
        'sub_task' => 'max_points',
        'grades' => 'grade_levels',
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

    public function groups(): array
    {
        return array_map(
            fn(DisabledGroupEntity $g) => $g->getName(),
            $this->assessment_api->disabledGroup()->all()
        );

    }

    public function disableBySetting(array $sections): array
    {
        $disabled = $this->names();

        foreach ($sections as $key => $section) {
            if (in_array($key, $disabled, true)) {
                $sections[$key] = $section->withDisabled(true)->withAdditionalOnLoadCode(
                    fn($id) => "il.EDUTIEK.disableInput($id)"
                );
            }
        }

        return $sections;
    }

    public function isDisabled(string $name): bool
    {
        return in_array($name, $this->names(), true);
    }

    public function toggleButton(): Button
    {
        global $DIC;
        $this->main_tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/templates/default/DisabledGroup/disabled-group.js');
        $click = $DIC['ui.signal_generator']->create();
        return $this->ui_factory->button()->toggle(($this->txt)(''), $click, $click)
            ->withAdditionalOnLoadCode(fn($id) => "$(document).on('$click', il.EDUTIEK.toggleDisabledInputs)");
    }

    public function toggles(string $post_url): array
    {
        if (!$this->perms->canEditTemplates()) {
            throw new Exception('permission_denied');
        }

        $groups = array_keys(self::DEFINITION);
        $disabled = $this->groups();

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

    public function form(string $post_url): Form
    {
        if (!$this->perms->canEditTemplates()) {
            throw new Exception('permission_denied');
        }
        $checkbox = $this->ui_factory->input()->field()->checkbox(...);
        $groups = array_keys(self::DEFINITION);
        $disabled = $this->groups();

        $fields = array_combine(
            $groups,
            array_map(
                fn($group) => $checkbox(($this->txt)(self::LANG_VARS[$group]))->withValue(in_array($group, $disabled, true)),
                $groups
            )
        );

        return $this->ui_factory->input()->container()->form()->standard($post_url, $fields);
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
            return;
        }
        ($this->with_form)($this->form(''), function (array $data): void {
            $this->assessment_api->disabledGroup()->saveAll(array_keys(array_filter($data)));
        });
    }

    private function names(): array
    {
        return array_merge(...array_map(
            fn($k) => self::DEFINITION[$k] ?? [],
            $this->groups()
        ));
    }
}
