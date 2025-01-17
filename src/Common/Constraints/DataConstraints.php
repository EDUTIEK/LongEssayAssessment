<?php

namespace ILIAS\Plugin\LongEssayAssessment\Common\Constraints;

use ILIAS\Data\Factory;
use ilLanguage;

class DataConstraints
{
    private Factory $dataFactory;
    private ilLanguage $language;

    public function __construct(Factory $dataFactory, ilLanguage $language)
    {
        $this->dataFactory = $dataFactory;
        $this->language = $language;
    }

    /**
     * Creates a constraint that can be used to check if an integer value is
     * greater or equal than the defined minimum.
     */
    public function minimumInteger(int $minimum) : MinimumInteger
    {
        return new MinimumInteger($minimum, $this->dataFactory, $this->language);
    }
}
