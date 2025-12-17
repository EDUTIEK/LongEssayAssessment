<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

abstract class Confirmation extends Action
{
    public function __construct(
        string $action_name,
        string $button_label,
        protected string $action_label,
        protected string $message,
        protected string $form_action,
        Type $action_type = Type::Standard,
        protected bool $require_all_enabled = false,
        protected ?string $disabled_message = null,
    ) {
        parent::__construct($action_name, $button_label, $action_type);
    }

    abstract public function itemName(Item $item): string;

    public function formAction()
    {
        return $this->form_action;
    }

    public function message()
    {
        return $this->message;
    }

    public function actionLabel(): string
    {
        return $this->action_label;
    }

    public function requireAllEnabled(): bool
    {
        return $this->require_all_enabled;
    }

    public function disabledMessage(): ?string
    {
        return $this->disabled_message;
    }
}
