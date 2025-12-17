<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ILIAS\UI\Implementation\Component as UI;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;

class Factory
{
    public function confirmation(
        string $action_name,
        string $button_label,
        string $action_label,
        string $message,
        string $form_action,
        $item_name_callback,
        $enabled_callback = null,
        Type $action_type = Type::Standard,
        bool $require_all_enabled = false,
        ?string $disabled_message = null,
    ) : Confirmation {
        return new class($action_name, $button_label, $action_label, $message, $form_action, $item_name_callback, $enabled_callback, $action_type,
        $require_all_enabled, $disabled_message) extends Confirmation {
            public function __construct(
                string $action_name,
                string $button_label,
                string $action_label,
                string $message,
                string $form_action,
                private $item_name_callback,
                private $enabled_callback,
                Type $action_type,
                bool $require_all_enabled,
                ?string $disabled_message
            ) {
                parent::__construct($action_name, $button_label, $action_label, $message, $form_action, $action_type,
                $require_all_enabled, $disabled_message);
            }

            public function itemName(Item $item) : string
            {
                return $this->callback($this->item_name_callback, [$item]);
            }

            public function enabled(Item $item) : bool
            {
                return $this->enabled_callback !== null ? $this->callback($this->enabled_callback, [$item]) : true;
            }
        };
    }

    public function form(
        string $action_name,
        string $button_label,
        string $action_label,
        $fields_callback,
        $save_callback,
        $enabled_callback = null,
        Type $action_type = Type::Standard
    ) : Form {
        return new class($action_name, $button_label, $action_label, $fields_callback, $save_callback, $enabled_callback, $action_type) extends Form {
            public function __construct(
                string $action_name,
                string $button_label,
                string $action_label,
                private $fields_callback,
                private $save_callback,
                private $enabled_callback,
                Type $action_type
            ) {
                parent::__construct($action_name, $button_label, $action_label, $action_type);
            }

            public function fields(array $items) : array
            {
                return $this->callback($this->fields_callback, $items);
            }

            public function save(array $items, array $data) : void
            {
                $this->callback($this->save_callback, $items, $data);
            }

            public function enabled(Item $item) : bool
            {
                return $this->enabled_callback !== null ? $this->callback($this->enabled_callback, [$item]) : true;
            }
        };
    }

    public function modal(
        string $action_name,
        string $button_label,
        $modal_callback,
        $enabled_callback = null,
        Type $action_type = Type::Standard
    ) : Modal {
        return new class($action_name, $button_label, $modal_callback, $enabled_callback, $action_type) extends Modal {
            public function __construct(
                string $action_name,
                string $button_label,
                private $modal_callback,
                private $enabled_callback,
                Type $action_type
            ) {
                parent::__construct($action_name, $button_label, $action_type);
            }

            public function modal(array $items) : UI\Modal\Modal
            {
                return $this->callback($this->modal_callback, $items);
            }

            public function enabled(Item $item) : bool
            {
                return $this->enabled_callback !== null ? $this->callback($this->enabled_callback, [$item]) : true;
            }
        };
    }

    public function direct(
        string $action_name,
        string $button_label,
        $action_callback,
        $enabled_callback = null,
        Type $action_type = Type::Standard
    ) : Direct {
        return new class($action_name, $button_label, $action_callback, $enabled_callback, $action_type) extends Direct {
            public function __construct(
                string $action_name,
                string $button_label,
                private $action_callback,
                private $enabled_callback,
                Type $action_type = Type::Standard
            ) {
                parent::__construct($action_name, $button_label, $action_type);
            }

            public function action(array $items) : void
            {
                $this->callback($this->action_callback, $items);
            }

            public function enabled(Item $item) : bool
            {
                return $this->enabled_callback !== null ? $this->callback($this->enabled_callback, [$item]) : true;
            }
        };
    }
}
