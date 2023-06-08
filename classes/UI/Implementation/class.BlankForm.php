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
	protected SignalGeneratorInterface $signal_generator;

	/**
	 * @var string
	 */
	protected string $post_url;

	/**
	 * @var Signal
	 */
	protected Signal $submit_signal;
	private Signal $submit_async_signal;

	private bool $asyncOnEnter = false;


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
	public function getSubmitSignal(): Signal
	{
		return $this->submit_signal;
	}

	/**
	 * @inheritdoc
	 */
	public function getSubmitAsyncSignal(): Signal
	{
		return $this->submit_async_signal;
	}

	public function withAsyncOnEnter(): \ILIAS\Plugin\LongEssayAssessment\UI\Component\BlankForm
	{
		$clone = clone $this;
		$clone->asyncOnEnter = true;

		return $clone;
	}

	public function isAsyncOnEnter(): bool
	{
		return $this->asyncOnEnter;
	}


	/**
	 * @inheritdoc
	 */
	public function getPostURL(): string
	{
		return $this->post_url;
	}

	public function initSignals()
	{
		$this->submit_signal = $this->signal_generator->create();
		$this->submit_async_signal = $this->signal_generator->create();
	}
}