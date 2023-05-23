<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Input\Field\Input;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Component\Triggerer;

/**
 * This describes numeric inputs.
 */
interface ItemListInput extends Input, Triggerer
{
	public function withListDataSource(Signal $signal): ItemListInput;
	public function getListDataSource(): ?Signal;
	public function getTriggerLoadSignal(): Signal;
}
