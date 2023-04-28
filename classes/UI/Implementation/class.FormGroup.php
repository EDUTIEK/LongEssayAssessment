<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Implementation\Component\Item\Group;

class FormGroup extends Group implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
{
	protected string $form_action = "";
	protected array $action_buttons = [];
	protected bool $cb_enabled = false;

	public function __construct($title, array $items, string $form_action)
	{
		parent::__construct($title, $items);
		$this->form_action = $form_action;
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

	public function withActionButtons(array $actions): \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
	{
		$clone = clone $this;
		$clone->action_buttons = $actions;

		return $clone;
	}

	public function getActionButtons(): array
	{
		return $this->action_buttons;
	}

	public function withCheckboxEnabled(bool $enabled): \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
	{
		$clone = clone $this;
		$clone->cb_enabled = $enabled;

		return $clone;
	}

	public function isCheckboxEnabled(): bool
	{
		return $this->cb_enabled;
	}
}