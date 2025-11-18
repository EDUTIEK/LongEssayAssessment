<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Input;

use ILIAS\UI\Implementation\Component\Input\Field\Textarea;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\DeriveApplyToFromTransform;
use ILIAS\Refinery\DeriveInvokeFromTransform;

class TinyMCE extends Textarea
{
    // todo: take from service
    private array $elements = ['strong', 'em', 'u', 'ol', 'li', 'ul', 'p', 'div',
                               'i', 'b', 'code', 'sup', 'sub', 'pre', 'strike', 'gap'];

    const MODE_MINI = 1;
    const MODE_STANDARD = 2;
    const MODE_EXTENDED = 3;
    const MODE_EXTENDED_TABLE = 4;
    const MODE_FULL = 5;

    public function __construct(
        DataFactory $data_factory,
        \ILIAS\Refinery\Factory $refinery,
        string $label,
        ?string $byline
    ) {
        FormInput::__construct($data_factory, $refinery, $label, $byline);
        $this->initTransformation();
    }

    private function initTransformation()
    {
        $this->operations = [];

//        $elements = $this->elements;
//        $this->setAdditionalTransformation($this->refinery->custom()->transformation(
//            fn ($x) => strip_tags($x, $elements)
//        ));
    }
}
