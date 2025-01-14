<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

use ILIAS\UI\Component\Item\Group;
use ILIAS\UI\Component\JavaScriptBindable;
use ILIAS\UI\Component\Signal;

/**
 * This describes numeric inputs.
 */
interface FormGroup extends Group, JavaScriptBindable
{
    /**
     * Add a post url
     *
     * @param string $link
     * @return FormGroup
     */
    public function withFormAction(string $link): FormGroup;

    /**
     * Post url of this form
     *
     * @return string
     */
    public function getFormAction(): string;

    /**
     * Change the label of the action button
     *
     * @param string $label
     * @return FormGroup
     */
    public function withActionLabel(string $label): FormGroup;

    /**
     * @return string
     */
    public function getActionLabel(): ?string;

    /**
     * This FormGroup without Actions
     *
     * @return Group
     */
    public function withoutActions(): Group;

    /**
     * Get the signal to submit this form
     *
     * @return Signal
     */
    public function getSubmitSignal(): Signal;

    /**
     * Get the signal to get the to connect a List Datasource with a ItemListInput
     *
     * @return Signal
     */
    public function getListDataSourceSignal(): Signal;
}
