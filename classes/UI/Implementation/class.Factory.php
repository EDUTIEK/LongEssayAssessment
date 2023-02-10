<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Data;
use ILIAS\Plugin\LongEssayAssessment\UI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FieldFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\IconFactory;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayAssessment\UI\Implementation
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
