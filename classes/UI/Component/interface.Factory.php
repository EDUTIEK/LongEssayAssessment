<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

/**
 * This is what a factory for input fields looks like.
 */
interface Factory
{
	public function field(): FieldFactory;

	public function icon(): IconFactory;

	public function formGroup(string $title, array $items, string $form_action): FormGroup;
}
