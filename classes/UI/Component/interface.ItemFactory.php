<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

/**
 * This is what a factory for input fields looks like.
 */
interface ItemFactory
{
	/**
	 * Item group with integrated formular and actions button known from the ilTable2GUI. It can be filled with Standard
	 * and FormItem which includes checkboxes. It can be triggered via submit signal and attached to a ItemListInput.
	 *
	 * @param string $title
	 * @param array $items
	 * @param string $form_action
	 * @return FormGroup
	 */
	public function formGroup(string $title, array $items, string $form_action): FormGroup;
	public function formItem($title): FormItem;
}
