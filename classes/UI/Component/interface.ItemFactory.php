<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

/**
 * This is what a factory for input fields looks like.
 */
interface ItemFactory
{
	public function formGroup(string $title, array $items, string $form_action): FormGroup;
	public function formItem(string $title): FormItem;
}
