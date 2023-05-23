<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\Item\Group;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

class FormGroup extends Group implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
{
	use JavaScriptBindable;

	protected string $form_action = "";
	private SignalGeneratorInterface $signal_generator;
	private \ILIAS\UI\Implementation\Component\Signal $list_data_source_signal;
	private \ILIAS\UI\Implementation\Component\Signal $submit_signal;

	public function __construct($title, array $items, string $form_action, SignalGeneratorInterface $signal_generator)
	{
		parent::__construct($title, $items);
		$this->form_action = $form_action;
		$this->signal_generator = $signal_generator;
		$this->initSignals();
	}


	public function withFormAction(string $link): \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
	{
		$clone = clone $this;
		$clone->form_action = $link;

		return $clone;
	}

	public function getFormAction(): string
	{
		return $this->form_action;
	}

	public function withoutActions(): Group
	{
		$clone = clone $this;
		$clone->actions = null;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getSubmitSignal(): Signal
	{
		return $this->submit_signal;
	}

	public function initSignals()
	{
		$this->submit_signal = $this->signal_generator->create();
		$this->list_data_source_signal = $this->signal_generator->create();
	}

	public function getListDataSourceSignal(): Signal
	{
		return $this->list_data_source_signal;
	}
}