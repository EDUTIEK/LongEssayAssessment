<?php

/* Copyright (c) 2016 Timon Amstutz <timon.amstutz@ilub.unibe.ch> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Button\Shy;
use ILIAS\UI\Component\Item\Item;
use ILIAS\UI\Component\Item\Standard;
use \ILIAS\UI\Component\Symbol\Icon\Icon;
use \ILIAS\UI\Component\Image\Image;
use \ILIAS\Data\Color;
use ILIAS\UI\Implementation\Component\Input\Field\Input;

/**
 * Interface Standard Item
 * @package ILIAS\UI\Component\Panel\Listing
 */
interface FormItem extends Standard
{
	/**
	 * Get a new item with the given properties as key-value pairs.
	 *
	 * The key is holding the title and the value is holding the content of the
	 * specific data set.
	 *
	 * @param array<string,string|Shy|Input> $properties Label => Content
	 * @return self
	 */
	public function withProperties(array $properties);

	/**
	 * Get the properties of the appointment.
	 *
	 * @return array<string,string|Shy|Input>		Title => Content
	 */
	public function getProperties();
}
