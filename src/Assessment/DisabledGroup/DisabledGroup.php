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
        $b = $this->ui_factory->button()->standard(($this->txt)('toggle'), '')->withAdditionalOnLoadCode(fn($id) => "$(document).on('$click', il.EDUTIEK.toggleDisabledInputs)");

        return $b->withOnClick($click);
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
