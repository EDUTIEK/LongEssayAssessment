<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\UI\Table\Item;
use stdClass;

class CorrectorItem extends Item
{
    public function __construct(
        int $id,
        private readonly string $name,
        private readonly string $login,
        private readonly int $first,
        private readonly ?int $second = null,
        private readonly int $not_started,
        private readonly int $open,
        private readonly int $authorized
    ) {
        parent::__construct($id);
    }
    public function getName() : string
    {
        return $this->name;
    }

    public function getLogin() : string
    {
        return $this->login;
    }

    public function getFirst() : int
    {
        return $this->first;
    }

    public function getSecond() : ?int
    {
        return $this->second;
    }

    public function getNotStarted() : int
    {
        return $this->not_started;
    }

    public function getOpen() : int
    {
        return $this->open;
    }

    public function getAuthorized() : int
    {
        return $this->authorized;
    }
}
