<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Component\Button\Button;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Component\Signal;
use ILIAS\UI\Implementation\Component\Item\Group;
use ILIAS\UI\Implementation\Component\JavaScriptBindable;
use ILIAS\UI\Implementation\Component\Modal\RoundTrip;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

class FormGroup extends Group implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
{
	use JavaScriptBindable;

	protected string $form_action = "";
	private SignalGeneratorInterface $signal_generator;
	private \ILIAS\UI\Implementation\Component\Signal $list_data_source_signal;
	private \ILIAS\UI\Implementation\Component\Signal $submit_signal;

	public function __construct($title, array $items, string $form_action, SignalGeneratorInterface $signal_generator)
	{
		parent::__construct($title, $items);
		$this->form_action = $form_action;
		$this->signal_generator = $signal_generator;
		$this->initSignals();
	}


	public function withFormAction(string $link): \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
	{
		$clone = clone $this;
		$clone->form_action = $link;

		return $clone;
	}

	public function getFormAction(): string
	{
		return $this->form_action;
	}

	public function withoutActions(): Group
	{
		$clone = clone $this;
		$clone->actions = null;

		return $clone;
	}

	/**
	 * @inheritdoc
	 */
	public function getSubmitSignal(): Signal
	{
		return $this->submit_signal;
	}

	public function initSignals()
	{
		$this->submit_signal = $this->signal_generator->create();
		$this->list_data_source_signal = $this->signal_generator->create();
	}

	public function getListDataSourceSignal(): Signal
	{
		return $this->list_data_source_signal;
	}

	/**
	 *
	 *
	 * @param Button $button
	 * @param RoundTrip $modal
	 * @param string $modal_source_link
	 * @return Button
	 */
	public function addModalTriggerCodeToButton(Button $button, RoundTrip $modal, string $modal_source_link, string $param_name): Button
	{
		$replace_signal_id = $modal->getReplaceSignal()->getId();
		$data_source_id = $this->list_data_source_signal->getId();
		$open_signal_id = $modal->getShowSignal();
		return $button->withAdditionalOnLoadCode(
			function ($id) use ($modal_source_link, $param_name, $replace_signal_id, $data_source_id, $open_signal_id) {
				return "
					$( '#{$id}' ).on( 'load_list_data_source_callback', function( event, signalData ) {
						ids = Object.keys(signalData['data_list']);
						
						if(ids.length == 0){
							$('#{$id}').effect('shake');
							return false;
						}
						
						n_url = '{$modal_source_link}' + '&{$param_name}=' + ids.join('/');
						
						$(this).trigger('{$replace_signal_id}',
							{
								'id' : '{$replace_signal_id}', 'event' : 'click',
								'triggerer' : $(this),
								'options' : JSON.parse('{\"url\":\"' + n_url + '\"}')
							}
						);
						$(this).trigger('{$open_signal_id}',
							{
								'id' : '{$open_signal_id}', 'event' : 'click',
								'triggerer' : $(this),
								'options' : []
							}
						);
   					 });
					
					
					$('#{$id}').click(function() { 
					
						$(document).trigger('{$data_source_id}',
							{
								'id' : '{$data_source_id}', 'event' : 'load_list_data_source',
								'triggerer' : $('#{$id}'),
								'options' : JSON.parse('[]')
							}
						);
						return false;
					}
				);";
			}
		);
	}

}