<?php

namespace ILIAS\Plugin\LongEssayAssessment\Assessment\Data;
use Edutiek\AssessmentService\Assessment\Data\ValidationErrorStore as ValidationErrorStoreInterface;
use Edutiek\AssessmentService\Assessment\Data\ValidationError;

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