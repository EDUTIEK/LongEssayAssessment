<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Component\JavaScriptBindable;
use ILIAS\UI\Component\Signal;

interface BlankForm extends Form, JavaScriptBindable
{
	/**
	 * Get the URL this form posts its result to.
	 *
	 * @return    string
	 */
	public function getPostURL();

	/**
	 * Get the signal to show this modal in the frontend
	 *
	 * @return Signal
	 */
	public function getSubmitSignal();
}