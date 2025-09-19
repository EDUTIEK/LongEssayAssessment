<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\UI\Component\Input\Input;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Button\Button;
use ILIAS\Refinery\Transformation;

abstract class Form extends Action
{
    private array $content = [];
    private array $action_buttons = [];
    private array $transformations = [];

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
        if($this->isCallable($this->content)) {
            return $this->callback($this->content, $items);
        }

        return $this->content;
    }

    /**
     * @param Item[] $items
     * @return Button[]
     */
    public function actionButtons(array $items): array
    {
        if($this->isCallable($this->action_buttons)) {
            return $this->callback($this->action_buttons, $items);
        }

        return $this->action_buttons;
    }

    /**
     * @return Transformation[]
     */
    public function transformations(array $items): array
    {
        if($this->isCallable($this->transformations)) {
            return $this->callback($this->transformations, $items);
        }

        return $this->transformations;
    }

    /**
     * @param Item[] $items
     * @param array $data
     * @return void
     */
    abstract public function save(array $items, array $data) : void;

    /**
     * @param Component[]|callable $content
     * @return $this
     */
    public function withContent(array|callable $content) : self
    {
        $new = clone $this;
        $new->content = $content;
        return $new;
    }

    /**
     * @param Button[]|callable $buttons
     * @return $this|Form
     */
    public function withActionButtons(array|callable $buttons)
    {
        $new = clone $this;
        $new->action_buttons = $buttons;
        return $new;
    }

    /**
     * @param Transformation[]|callable $buttons
     * @return $this|Form
     */
    public function withTransformations(array|callable $transformations)
    {
        $new = clone $this;
        $new->transformations = $transformations;
        return $new;
    }
}