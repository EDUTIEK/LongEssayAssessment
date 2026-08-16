<?php

declare(strict_types=1);

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
     * Creates a constraint that can be used to check if value is
     * greater or equal than the defined minimum.
     */
    public function minimum(int $minimum): Minimum
    {
        return new Minimum($minimum, $this->dataFactory, $this->language);
    }

    /**
     * Creates a constraint that can be used to check if value is
     * lower or equal than the defined maximum.
     */
    public function maximum(int $minimum): Maximum
    {
        return new Maximum($minimum, $this->dataFactory, $this->language);
    }

    /**
     * Creates a constraint that requires a checkbox to be checked
     */
    public function checked(): Checked
    {
        return new Checked($this->dataFactory, $this->language);
    }
}
