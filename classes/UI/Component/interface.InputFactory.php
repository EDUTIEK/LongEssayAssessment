<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

interface InputFactory
{
	/**
	 * ---
	 * description:
	 *   purpose: >
	 *      A numeric field is used to retrieve integer values from the user.
	 *   composition: >
	 *      Numeric inputs will render an input-tag with type="number".
	 *   effect: >
	 *      The field does not accept any data other than numeric values. When
	 *      focused most browser will show a small vertical rocker to increase
	 *      and decrease the value in the field.
	 * rules:
	 *   usage:
	 *     1: Number Inputs MUST NOT be used for binary choices.
	 *     2: >
	 *         Magic numbers such as -1 or 0 to specify “limitless” or smoother
	 *         options MUST NOT be used.
	 *     3: A valid input range SHOULD be specified.
	 *
	 * ---
	 *
	 * @param string $label
	 * @param string|null $byline
	 *
	 * @return Numeric
	 */
	public function numeric(string $label, string $byline = null): Numeric;

	/**
	 * An input field which is fed from another component like a form group
	 * the filling of this input happens by a trigger, mostly a button which activates the form modal
	 *
	 * @param string $label
	 * @param string|null $byline
	 * @return ItemListInput
	 */
	public function itemList(string $label, string $byline = null): ItemListInput;

	/**
	 * A form without action buttons to integrate into a modal. It is triggered via submit signal.
	 *
	 * @param string $post_url
	 * @param array $inputs
	 * @return BlankForm
	 */
	public function blankForm(string $post_url, array $inputs): BlankForm;
}