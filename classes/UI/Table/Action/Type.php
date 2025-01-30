<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Table\Action;

enum Type
{
    case Standard; // Action is executed on Single Items and Multiple Items
    case Single; // Action is executed on Single Items only
    case Multi; // Action is executed on Multiple Items only
    case Global; // Action is executed on Global with empty Item
}