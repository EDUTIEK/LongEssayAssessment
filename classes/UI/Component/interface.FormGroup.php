<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Item\Group;
use ILIAS\UI\Component\JavaScriptBindable;
use ILIAS\UI\Component\Signal;

/**
 * This describes numeric inputs.
 */
interface FormGroup extends Group, JavaScriptBindable
{
	public function withFormAction(string $link): FormGroup;

	public function getFormAction(): string;

	public function withoutActions(): Group;

	/**
	 * Get the signal to show this modal in the frontend
	 *
	 * @return Signal
	 */
	public function getSubmitSignal(): Signal;

	/**
	 * Get the signal to show this modal in the frontend
	 *
	 * @return Signal
	 */
	public function getListDataSourceSignal(): Signal;
}