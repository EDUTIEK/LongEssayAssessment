<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Input;

use Edutiek\AssessmentService\System\Data\FormattingOptions;
use Edutiek\AssessmentService\System\Data\HeadlineScheme;
use ILIAS\UI\Implementation\Component\Input\Field\Textarea;
use ILIAS\Data\Factory as DataFactory;
use ILIAS\UI\Implementation\Component\Input\Field\FormInput;
use ILIAS\Refinery\Transformation;
use ILIAS\Refinery\DeriveApplyToFromTransform;
use ILIAS\Refinery\DeriveInvokeFromTransform;

class TinyMCE extends Textarea
{
    private ?FormattingOptions $formatting_options;
    private ?HeadlineScheme $headline_scheme;

    public function __construct(
        DataFactory $data_factory,
        \ILIAS\Refinery\Factory $refinery,
        string $label,
        ?string $byline
    ) {
        FormInput::__construct($data_factory, $refinery, $label, $byline);
    }

    public function getFormattingOptions() : FormattingOptions
    {
        return $this->formatting_options ?? FormattingOptions::EXTENDED;
    }

    public function withFormattingOptions(FormattingOptions $formatting_options) : TinyMCE
    {
        $clone = clone($this);
        $clone->formatting_options = $formatting_options;
        return $clone;
    }

    public function getHeadlineScheme() : HeadlineScheme
    {
        return $this->headline_scheme ?? HeadlineScheme::THREE;
    }

    public function withHeadlineScheme(HeadlineScheme $headline_scheme) : TinyMCE
    {
        $clone = clone($this);
        $clone->headline_scheme = $headline_scheme;
        return $clone;
    }
}
