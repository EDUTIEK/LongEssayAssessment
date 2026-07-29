<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Input;

use ILIAS\Data;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Implementation\Component\Input\Field\Factory;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ILIAS\UI\Implementation\Component\Input\Field\Textarea;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Component\Input\Field\ColorPicker;

class InputFactory
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
    public function numeric($label, $byline = null) : Numeric
    {
        return new Numeric($this->data_factory, $this->refinery, $label, $byline);
    }

    /**
     * @inheritdoc
     */
    public function itemList($label, $byline = null) : ItemListInput
    {
        return new ItemListInput($this->data_factory, $this->refinery, $label, $byline, $this->signal_generator);
    }

    /**
     * @inheritdoc
     */
    public function blankForm(string $post_url, array $inputs): BlankForm
    {
        return new BlankForm(
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

    public function tinyMCE($label, $byline = null): TinyMCE
    {
        $tiny = new TinyMCE($this->data_factory, $this->refinery, $label, $byline);

        // this method comes with ILIAs 10.9
        if (method_exists($tiny, 'withoutStripTags')) {
            return $tiny->withoutStripTags();
        }
        return $tiny;
    }

    public function info($label, $byline = null): Info
    {
        return new Info($this->data_factory, $this->refinery, $label, $byline);
    }

    public function colorSelect(string $label, ?string $byline = null): ColorPicker
    {
        return $this->input_factory->colorPicker($label, $byline);
    }
}
