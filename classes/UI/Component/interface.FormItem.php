<?php

/* Copyright (c) 2016 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Button\Shy;
use ILIAS\UI\Component\Item\Item;
use ILIAS\UI\Component\Item\Standard;
use \ILIAS\UI\Component\Symbol\Icon\Icon;
use \ILIAS\UI\Component\Image\Image;
use \ILIAS\Data\Color;
use ILIAS\UI\Implementation\Component\Input\Input;
use ILIAS\UI\Implementation\Component\Input\NameSource;

/**
 * Interface Standard Item
 * @package ILIAS\UI\Component\Panel\Listing
 */
interface FormItem extends Standard
{
    /**
     * Set Name or ID of this Item
     * @param string $name
     * @return mixed
     */
    public function withName(string $name);

    /**
     * Get Name or ID of this Item
     * @return string|null
     */
    public function getName(): ?string;
}
