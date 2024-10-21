<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Data;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\AsyncForm;
use ILIAS\UI\Implementation\Component\Input\Field\Factory;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ILIAS\UI\Implementation\Component\Input\Field\Textarea;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

class InputFactory implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\InputFactory
{
    /**
     * @var    Data\Factory
     */
    protected $data_factory;

    /**
     * @var SignalGeneratorInterface
     */
    protected $signal_generator;

    /**
     * @var \ILIAS\Refinery\Factory
     */
    private $refinery;

    /**
     * @var	\ilLanguage
     */
    protected $lng;

    /**
     * @var \ILIAS\UI\Implementation\Component\Input\Field\Factory
     */
    private \ILIAS\UI\Implementation\Component\Input\Field\Factory $input_factory;

    /**
     * Factory constructor.
     *
     * @param Factory $input_factory
     * @param SignalGeneratorInterface $signal_generator
     * @param Data\Factory $data_factory
     * @param \ILIAS\Refinery\Factory $refinery
     * @param \ilLanguage $lng
     */
    public function __construct(
        Factory $input_factory,
        SignalGeneratorInterface $signal_generator,
        Data\Factory $data_factory,
        \ILIAS\Refinery\Factory $refinery,
        \ilLanguage $lng
    ) {
        $this->input_factory = $input_factory;
        $this->signal_generator = $signal_generator;
        $this->data_factory = $data_factory;
        $this->refinery = $refinery;
        $this->lng = $lng;
    }

    /**
     * @inheritdoc
     */
    public function numeric($label, $byline = null) : \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Numeric
    {
        return new Numeric($this->data_factory, $this->refinery, $label, $byline);
    }

    /**
     * @inheritdoc
     */
    public function itemList($label, $byline = null) : \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\ItemListInput
    {
        return new ItemListInput($this->data_factory, $this->refinery, $label, $byline, $this->signal_generator);
    }

    /**
     * @inheritdoc
     */
    public function asyncForm(string $post_url, array $inputs): AsyncForm
    {
        return new \ILIAS\Plugin\LongEssayAssessment\UI\Implementation\AsyncForm(
            $this->input_factory,
            $post_url,
            $inputs,
            $this->signal_generator
        );
    }

    /**
     * @inheritdoc
     */
    public function textareaModified($label, $byline = null): Textarea
    {
        return new class($this->data_factory, $this->refinery, $label, $byline) extends Textarea {
            public function __construct(DataFactory $data_factory, \ILIAS\Refinery\Factory $refinery, $label, $byline)
            {
                FormInput::__construct($data_factory, $refinery, $label, $byline);//Skip striptags transformation
            }
        };
    }
}
