<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Data\Factory as DataFactory;
use ILIAS\Refinery\Transformation;
use ILIAS\UI\Implementation\Component\Input\Field\Input;

/**
 * This implements the numeric input.
 */
class Numeric extends Input  implements \ILIAS\Plugin\LongEssayAssessment\UI\Component\Numeric
{
    /**
     * @var bool
     */
    private $complex = false;

	/**
	 * @var float
	 */
	private $step = 1.;

    /**
     * Numeric constructor.
     *
     * @param DataFactory $data_factory
     * @param \ILIAS\Refinery\Factory $refinery
     * @param             $label
     * @param             $byline
     */
    public function __construct(
        DataFactory $data_factory,
        \ILIAS\Refinery\Factory $refinery,
        $label,
        $byline
    ) {
        parent::__construct($data_factory, $refinery, $label, $byline);

        /**
         * @var $trafo_numericOrNull Transformation
         */
        $trafo_numericOrNull = $this->refinery->byTrying([
            $this->refinery->kindlyTo()->null(),
			$this->refinery->kindlyTo()->float()
        ])
        ->withProblemBuilder(function ($txt, $value) {
            return $txt("ui_numeric_only");
        });

        $this->setAdditionalTransformation($trafo_numericOrNull);
    }

    /**
     * @inheritdoc
     */
    protected function isClientSideValueOk($value) : bool
    {
        return is_numeric($value) || $value === "" || $value === null;
    }

    /**
     * @inheritdoc
     */
    protected function getConstraintForRequirement()
    {
		if($this->step === 1.){
			return $this->refinery->kindlyTo()->int();
		}
        return $this->refinery->kindlyTo()->float();
    }

    /**
     * @inheritdoc
     */
    public function getUpdateOnLoadCode() : \Closure
    {
        return function ($id) {
            $code = "$('#$id').on('input', function(event) {
				il.UI.input.onFieldUpdate(event, '$id', $('#$id').val());
			});
			il.UI.input.onFieldUpdate(event, '$id', $('#$id').val());";
            return $code;
        };
    }

    /**
     * @inheritdoc
     */
    public function isComplex() : bool
    {
        return $this->complex;
    }

	/**
	 * @inheritDoc
	 */
	public function getStep(): float
	{
		return $this->step;
	}

	/**
	 * @inheritDoc
	 */
	public function withStep(float $step): \ILIAS\UI\Component\Input\Field\Input
	{
		$clone = clone $this;
		$clone->step = $step;

		return $clone;
	}
}
