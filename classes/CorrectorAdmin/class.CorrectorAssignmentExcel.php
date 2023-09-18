<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CorrectorAssignmentExcel extends \ilExcel
{
    public function addDropdownCol($a_row, $a_col, $a_target_formula)
    {
        $objValidation = new DataValidation();
        //$objValidation = $this->workbook->getActiveSheet()->getCellByColumnAndRow($a_row, $a_col)->getDataValidation();
        $objValidation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
        //$objValidation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $objValidation->setAllowBlank(true);
        //$objValidation->setShowInputMessage(true);
        //$objValidation->setShowErrorMessage(true);
        $objValidation->setShowDropDown(true);
        //$objValidation->setErrorTitle('Input error');
        //$objValidation->setError('Value is not in list.');
        //$objValidation->setPromptTitle('Pick from list');
        //$objValidation->setPrompt('Please pick a value from the drop-down list.');
        $objValidation->setFormula1($a_target_formula);
        $this->workbook->getActiveSheet()->getCellByColumnAndRow($a_row, $a_col)->setDataValidation(clone $objValidation);
    }
}