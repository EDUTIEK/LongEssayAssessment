<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayTask\UI\Implementation;

use ILIAS\Data;
use ILIAS\Plugin\LongEssayTask\UI;
use ILIAS\Plugin\LongEssayTask\UI\Component\FieldFactory;
use ILIAS\Plugin\LongEssayTask\UI\Component\IconFactory;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayTask\UI\Implementation
 */
class Factory implements UI\Component\Factory
{
	private FieldFactory $field_factory;
	private IconFactory $icon_factory;

	public function __construct(
		FieldFactory $field_factory,
		IconFactory $icon_factory
	)
	{
		$this->field_factory = $field_factory;
		$this->icon_factory = $icon_factory;
	}


	public function field(): FieldFactory
	{
		return $this->field_factory;
	}

	public function icon(): IconFactory
	{
		return $this->icon_factory;
	}
}
