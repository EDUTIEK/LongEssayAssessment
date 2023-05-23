<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component as C;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\Input;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Form;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

class BlankForm extends Form implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm
{
	use JavaScriptBindable;

	/**
	 * @var SignalGeneratorInterface
	 */
	protected $signal_generator;

	/**
	 * @var string
	 */
	protected $post_url;

	/**
	 * @var Signal
	 */
	protected $submit_signal;


	public function __construct(Input\Field\Factory $input_factory, $post_url, array $inputs, SignalGeneratorInterface $signal_generator)
	{
		parent::__construct($input_factory, $inputs);
		$this->checkStringArg("post_url", $post_url);
		$this->post_url = $post_url;
		$this->signal_generator = $signal_generator;
		$this->initSignals();
	}

	/**
	 * @inheritdoc
	 */
	public function getSubmitSignal()
	{
		return $this->submit_signal;
	}


	/**
	 * @inheritdoc
	 */
	public function getPostURL()
	{
		return $this->post_url;
	}

	public function initSignals()
	{
		$this->submit_signal = $this->signal_generator->create();
	}

}