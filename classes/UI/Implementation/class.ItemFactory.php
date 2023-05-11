<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Factory as ILIASIconFactory;

class ItemFactory implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\ItemFactory
{
	private ILIASIconFactory $factory;
	private \ilPlugin $plugin;
	public function __construct(ILIASIconFactory $factory, \ilPlugin $plugin)
	{
		$this->factory = $factory;
		$this->plugin = $plugin;
	}

	public function formGroup(string $title, array $items, string $form_action): FormGroup
	{
		return new \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\FormGroup(
			$title,
			$items,
			$form_action
		);
	}

	public function formItem(string $title): FormItem
	{
		return new \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\FormItem($title);
	}
}