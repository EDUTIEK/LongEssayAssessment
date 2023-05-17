<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FieldFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\IconFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\ItemFactory;

/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayAssessment\UI\Implementation
 */
class Factory implements UI\Component\Factory
{
	private FieldFactory $field_factory;
	private IconFactory $icon_factory;
	private ItemFactory $item_factory;

	public function __construct(
		FieldFactory $field_factory,
		IconFactory $icon_factory,
		ItemFactory $item_factory
	)
	{
		$this->field_factory = $field_factory;
		$this->icon_factory = $icon_factory;
		$this->item_factory = $item_factory;
	}


	public function field(): FieldFactory
	{
		return $this->field_factory;
	}

	public function icon(): IconFactory
	{
		return $this->icon_factory;
	}

	public function item(): ItemFactory
	{
		return $this->item_factory;
	}
}
