<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Item\Group;

/**
 * This describes numeric inputs.
 */
interface FormGroup extends Group
{
	public function withFormAction(string $link): FormGroup;

	public function getFormAction(): string;
	public function withActionButtons(array $actions): FormGroup;

	public function getActionButtons(): array;

	public function withCheckboxEnabled(bool $enabled): FormGroup;

	public function isCheckboxEnabled(): bool;

}