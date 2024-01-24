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
    /**
     * Attach a DataSource signal to get its input values
     *
     * @param Signal $signal
     * @return ItemListInput
     */
    public function withListDataSource(Signal $signal): ItemListInput;

    /**
     * DataSource signal
     * @return Signal|null
     */
    public function getListDataSource(): ?Signal;

    /**
     * A Signal which triggers the loading of a DataSource like the FormGroup
     *
     * @return Signal
     */
    public function getTriggerLoadSignal(): Signal;
}
