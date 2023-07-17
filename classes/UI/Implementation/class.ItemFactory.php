<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\FormItem;
use ILIAS\UI\Component\Button\Shy;
use ILIAS\UI\Component\Link\Link;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Factory as ILIASIconFactory;

class ItemFactory implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\ItemFactory
{
	private ILIASIconFactory $factory;
	private \ilPlugin $plugin;
	private SignalGeneratorInterface $signal_generator;

	public function __construct(ILIASIconFactory $factory, \ilPlugin $plugin, SignalGeneratorInterface $signal_generator)
	{
		$this->factory = $factory;
		$this->plugin = $plugin;
		$this->signal_generator = $signal_generator;
	}

	public function formGroup(string $title, array $items, string $form_action): FormGroup
	{
		return new \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\FormGroup(
			$title,
			$items,
			$form_action,
			$this->signal_generator
		);
	}

	/**
	 * @param Shy|Link|string $title
	 * @return FormItem
	 */
	public function formItem($title): FormItem
	{
		return new \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\FormItem($title);
	}
}