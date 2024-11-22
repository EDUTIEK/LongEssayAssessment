<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

use ReflectionMethod;
use ReflectionFunction;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\TableItem;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use ReflectionNamedType;

abstract class Action
{
    public function __construct(
        protected string $name,
        protected string $button_label,
        protected Type $type = Type::Standard,
    ) {
    }

    private function isMultiple($callback)
    {
        if (is_callable($callback)) {
            if (is_array($callback)) {
                // For class methods
                $reflection = new ReflectionMethod($callback[0], $callback[1]);
            } else {
                // For functions
                $reflection = new ReflectionFunction($callback);
            }

            $parameters = $reflection->getParameters();

            if(count($parameters) > 1) {
                throw new \Exception("Given callback needs at least one parameter of type array or Table\Item");
            }

            $fparameter_type = $type = $parameters[0]->getType();

            if ($type instanceof ReflectionNamedType) {
                $typeName = $type->getName();
                if (! ($typeName === 'array' || is_a($typeName, 'ILIAS\Plugin\LongEssayAssessment\UI\Table2\Item'))) {
                    throw new \Exception("Given callbacks first parameter need to be of type array or Table\Item");
                }
                return $typeName === 'array';
            } else {
                throw new \Exception("Given callbacks first parameter need to be of type array or Table\Item");
            }
        } else {
            throw new \Exception("Given callback is not callable");
        }
    }

    protected function callback($callback, ...$args) : mixed
    {
        if(!$this->isMultiple($callback)) {
            $args[0] = array_pop($args[0]); // unpack single item array
        }

        if(is_array($callback)) {
            return $callback[0]->$callback[1](...$args);
        } else {
            return $callback(...$args);
        }
    }

    abstract public function enabled(Item $item) : bool;

    public function name() : string
    {
        return $this->name;
    }

    public function label() : string
    {
        return $this->button_label;
    }

    public function type() : Type
    {
        return $this->type;
    }
}
