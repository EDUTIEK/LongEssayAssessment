<?php

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\WorkingTime;


use Edutiek\AssessmentService\Assessment\WorkingTime\ValidationErrorStore as ValidationErrorStoreInterface;
use Edutiek\AssessmentService\Assessment\WorkingTime\ValidationError;

class ValidationErrorStore implements ValidationErrorStoreInterface
{
    /** @var ValidationError[] */
    private $validation_errors = [];

    public function addValidationError(ValidationError $error) : void
    {
        $this->validation_errors[] = $error;
    }

    public function getValidationErrors(): array
    {
        return $this->validation_errors;
    }
}