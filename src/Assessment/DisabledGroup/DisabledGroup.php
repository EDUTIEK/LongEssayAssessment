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
use ILIAS\UI\Component\Modal\Modal;
use Closure;
use Edutiek\AssessmentService\Assessment\Permissions\ReadService as Permissions;
use Exception;

class DisabledGroup
{
    public const DEFINITION = [
        'example' => ['template'],
        'grades' => ['grades'],
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
    )
    {
    }

    public function groups(): array
    {
        return array_map(
            fn(DisabledGroupEntity $g) => $g->getName(),
            $this->assessment_api->disabledGroup()->get()
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
        $click = $DIC['ui.signal_generator']->create();
        $b = $this->ui_factory->button()->standard('toggle', '')->withAdditionalOnLoadCode(fn($id) => "$(document).on('$click', il.EDUTIEK.toggleDisabledInputs)");

        return $b->withOnClick($click);
    }

    public function modal(string $post_url): Modal
    {
        if (!$this->perms->canEditTemplates()) {
            throw new Exception('permission_denied');
        }
        $checkbox = $this->ui_factory->input()->field()->checkbox(...);
        $groups = array_keys(self::DEFINITION);
        $disabled = $this->groups();

        return $this->ui_factory->modal()->roundtrip(($this->txt)('disabled_group_modal'), null, array_combine(
            $groups,
            array_map(
                fn($group) => $checkbox($group)->withValue(in_array($group, $disabled, true)),
                $groups
            )
        ), $post_url);
    }

    public function modalWithButton(string $post_url): array
    {
        if (!$this->perms->canEditTemplates()) {
            return [];
        }
        $this->main_tpl->addJavaScript('Customizing/global/plugins/Services/Repository/RepositoryObject/LongEssayAssessment/templates/default/DisabledGroup/disabled-group.js');
        $modal = $this->modal($post_url);
        $button = $this->ui_factory->button()->standard(($this->txt)('toggle_disabled_groups'), '');
        $button = $button->withOnClick($modal->getShowSignal());

        return [$button, $modal];
    }

    public function saveModal(): void
    {
        $modal = $this->modal('');
        ($this->with_form)($modal, function (array $data): void {
            $this->assessment_api->disabledGroup()->save(array_keys(array_filter($data)));
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
