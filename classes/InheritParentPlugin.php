<?php

namespace ILIAS\Plugin\LongEssayAssessment;

use ilPluginLanguage;
use ilTemplate;

trait InheritParentPlugin
{
    private ? \ilLongEssayAssessmentPlugin $parent_plugin = null;

    public function getParentPlugin() : \ilLongEssayAssessmentPlugin
    {
        return $this->parent_plugin ?? \ilLongEssayAssessmentPlugin::getInstance();
    }

    public function getDirectory(): string
    {
        return $this->getParentPlugin()->getDirectory();
    }

    protected function getLanguageHandler(): ilPluginLanguage
    {
        return $this->getParentPlugin()->getLanguageHandler();
    }

    public function getStyleSheetLocation(string $a_css_file): string
    {
        return $this->getParentPlugin()->getStyleSheetLocation($a_css_file);
    }

    public function getTemplate(string $a_template, bool $a_par1 = true, bool $a_par2 = true): ilTemplate
    {
        return $this->getParentPlugin()->getTemplate($a_template, $a_par1, $a_par2);
    }

    public function txt(string $a_var): string
    {
        return $this->getParentPlugin()->txt($a_var);
    }
}