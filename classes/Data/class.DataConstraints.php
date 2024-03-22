<?php

namespace ILIAS\Plugin\LongEssayAssessment\Data;

use ILIAS\Data\Factory;
use ILIAS\Plugin\LongEssayAssessment\Data\Constraints\MinimumInteger;

class DataConstraints
{
    /**
     * @var Factory
     */
    private $dataFactory;

    /**
     * @var \ilLanguage
     */
    private $language;

    public function __construct(Factory $dataFactory, \ilLanguage $language)
    {
        $this->dataFactory = $dataFactory;
        $this->language = $language;
    }

    /**
     * Creates a constraint that can be used to check if an integer value is
     * greater or equal than the defined lower limit.
     *
     * @param int $minimum - lower limit for the new constraint
     * @return MinimumInteger
     */
    public function minimumInteger(int $minimum) : MinimumInteger
    {
        return new MinimumInteger($minimum, $this->dataFactory, $this->language);
    }
}
