<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Input\Field\Textarea;

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

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     A textarea is intended for entering multi-line texts.
	 *   composition: >
	 *      Textarea fields will render an textarea HTML tag.
	 *      If a limit is set, a byline about limitation is automatically set.
	 *   effect: >
	 *      Textarea inputs are NOT restricted to one line of text.
	 *      A textarea counts the amount of character input by user and displays the number.
	 *   rivals:
	 *      text field: Use a text field if users should input only one line of text.
	 *      numeric field: Use a numeric field if users should input numbers.
	 *      alphabet field: >
	 *          Use an alphabet field if the user should input single letters.
	 *
	 * rules:
	 *   usage:
	 *     1: Textarea Input MUST NOT be used for choosing from predetermined options.
	 *     2: >
	 *         Textarea input MUST NOT be used for numeric input, a Numeric Field is
	 *         to be used instead.
	 *     3: >
	 *         Textarea Input MUST NOT be used for letter-only input, an Alphabet Field
	 *         is to be used instead.
	 *     4: >
	 *         Textarea Input MUST NOT be used for single-line input, a Text Field
	 *         is to be used instead.
	 *     5: >
	 *         If a min. or max. number of characters is set for textarea, a byline MUST
	 *         be added stating the number of min. and/or max. characters.
	 *   interaction:
	 *     1: >
	 *         Textarea Input MAY limit the number of characters, if a certain length
	 *         of text-input may not be exceeded (e.g. due to database-limitations).
	 *
	 * ---
	 * @param    string      $label
	 * @param    string|null $byline
	 * @return   Textarea
	 */
	public function textareaModified($label, $byline = null): Textarea;
}