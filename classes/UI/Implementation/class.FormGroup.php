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
    protected ?string $action_label = null;

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

    public function withActionLabel(string $label): \ILIAS\Plugin\LongEssayAssessment\UI\Component\FormGroup
    {
        $clone = clone $this;
        $clone->action_label = $label;

        return $clone;
    }

    public function getActionLabel(): ?string
    {
        return $this->action_label;
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
     * Generates Signal which can be used to set a data source callback signal
     *
     * @return Signal
     */
    public function generateDSCallbackSignal(): Signal
    {
        return $this->signal_generator->create();
    }

    /**
     * Adds the handler to a data source callback signal to a modal to build a async render url including all selected ids
     *
     * @param Modal $modal
     * @param $modal_source_link
     * @param $param_name
     * @param Signal $callback
     * @return Modal
     */
    public function addDSModalTriggerToModal(Modal $modal, $modal_source_link, $param_name, Signal $callback): Modal
    {
        $callback_id = $callback->getId();
        $modal_source_link = str_replace("\\", "\\\\", $modal_source_link);
        $modal = $modal->withAsyncRenderUrl($modal_source_link);#Prefill async render URL even its not working. It is needed for a proper rendering

        return $modal->withOnLoadCode(
            function ($id) use ($modal_source_link, $param_name, $callback_id) {
                return "
					$( document ).on( '{$callback_id}', function( event, signalData ) {
						ids = Object.keys(signalData['options']['data_list']);
						if(ids.length == 0){
							$('#{$id}').effect('shake');
							return false;
						}
						
						n_url = '{$modal_source_link}' + '&{$param_name}=' + ids.join('/');
						
						il.UI.modal.showModal(
							'{$id}', 
							{'url': '#{$id}', 'ajaxRenderUrl': n_url, 'keyboard': true},
							signalData
						); 
						return false;
   					 });";
            }
        );
    }

    /**
     * Adds the datasource trigger to a button to call an async modal open with an async render url containing all ids
     *
     * @param Button $button
     * @param Signal $callback
     * @return Button
     */
    public function addDSModalTriggerToButton(Button $button, Signal $callback): Button
    {
        $data_source_id = $this->list_data_source_signal->getId();
        $callback_id = $callback->getId();

        return $button->withAdditionalOnLoadCode(
            function ($id) use ($data_source_id, $callback_id) {
                return "
					$('#{$id}').click(function() { 
						$(document).trigger('{$data_source_id}',
							{
								'id' : '{$data_source_id}', 'event' : 'load_list_data_source',
								'triggerer' : $('#{$id}'),
								'options' : JSON.parse('{\"callback\":\"{$callback_id}\"}')
							}
						);
						return false;
					});";
            }
        );
    }

}
