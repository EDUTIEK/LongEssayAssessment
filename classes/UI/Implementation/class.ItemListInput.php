<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Factory;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Implementation\Component\Input\Field\Input;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\SignalGenerator;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Implementation\Component\Triggerer;

class ItemListInput extends Input implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\ItemListInput
{
	use Triggerer;
	use ComponentHelper;
	use JavaScriptBindable;

	private SignalGeneratorInterface $signal_generator;
	private ?Signal $trigger_load;

	public function __construct(DataFactory $data_factory, Factory $refinery, $label, $byline, SignalGeneratorInterface $signal_generator)
	{
		parent::__construct($data_factory, $refinery, $label, $byline);
		$this->signal_generator = $signal_generator;
		$this->initSignals();
	}


	/**
	 * @inheritdoc
	 */
	protected function isClientSideValueOk($value) : bool
	{
		return is_array($value) || $value === null;
	}

	/**
	 * @inheritdoc
	 */
	protected function getConstraintForRequirement() : ?\ILIAS\Refinery\Constraint
    {
		return $this->refinery->kindlyTo()->listOf($this->refinery->kindlyTo()->string());
	}

	/**
	 * @inheritdoc
	 */
	public function getUpdateOnLoadCode() : \Closure
	{
		return function ($id) {
			return "$('#$id').on('input', function(event) {
				il.UI.input.onFieldUpdate(event, '$id', $('#$id').val());
			});
			il.UI.input.onFieldUpdate(event, '$id', $('#$id').val());";
		};
	}

	/**
	 * @inheritDoc
	 */
	public function withListDataSource(Signal $signal): ItemListInput
	{
		return $this->withTriggeredSignal($signal, 'load_list_data_source');
	}

	/**
	 * @inheritDoc
	 */
	public function getTriggerLoadSignal(): Signal
	{
		return $this->trigger_load;
	}

	public function initSignals()
	{
		$this->trigger_load = $this->signal_generator->create();
	}

	/**
	 * @inheritDoc
	 */
	public function getListDataSource(): ?Signal
	{
		$signals = $this->getTriggeredSignals();
		if(count($signals) > 0){
			return $signals[0]->getSignal();
		}
		 return null;
	}
}