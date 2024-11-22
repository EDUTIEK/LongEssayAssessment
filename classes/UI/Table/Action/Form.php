<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\UI\Component\Input\Input;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\UI\Component\Component;

abstract class Form extends Action
{
    public function __construct(
        string $action_name,
        string $button_label,
        protected string $action_label,
        Type $action_type = Type::Standard
    ) {
        parent::__construct($action_name, $button_label, $action_type);
    }

    public function actionLabel(): string
    {
        return $this->action_label;
    }

    /**
     *
     * @param Item[] $items
     * @return Input[]
     */
    abstract public function fields(array $items): array;

    /**
     * @param Item[] $items
     * @return Component[]
     */
    public function content(array $items): array
    {
        return [];
    }

    /**
     * @param Item[] $items
     * @param array $data
     * @return void
     */
    abstract public function save(array $items, array $data) : void;
}