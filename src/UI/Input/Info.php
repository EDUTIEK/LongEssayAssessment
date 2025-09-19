<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Input;

use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use Closure;
use ILIAS\Refinery\Constraint;
use ILIAS\UI\Component\Component;

class Info extends FormInput
{
    private array|Component|null $info = null;

    protected function getConstraintForRequirement(): ?Constraint
    {
        return null;
    }

    public function getUpdateOnLoadCode(): Closure
    {
        return fn($id) => "";
    }

    protected function isClientSideValueOk($value): bool
    {
        return true;
    }

    /**
     * @return Component[]|Component|null
     */
    public function getInfo(): array|Component|null
    {
        return $this->info;
    }

    /**
     * @param Component[]|Component|null $info
     * @return $this
     */
    public function withInfo(array|Component|null $info): self
    {
        $clone = clone $this;
        $clone->info = $info;

        return $clone;
    }
}