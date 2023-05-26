<?php

/* Copyright (c) 2017 Alex Killing <killing@leifos.de> Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Implementation\Component\Item\Standard;

class FormItem extends Standard implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem
{
	protected ?string $name;

	/**
	 * @ineritdoc
	 */
	public function withName(string $name): FormItem
	{
		$clone = clone $this;
		$clone->name = $name;

		return $clone;
	}

	/**
	 * @ineritdoc
	 */
	public function getName(): ?string
	{
		return $this->name;
	}
}
