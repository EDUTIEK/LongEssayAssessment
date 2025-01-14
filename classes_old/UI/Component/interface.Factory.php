<?php
namespace ILIAS\Plugin\LongEssayAssessment\UI\Component;

/**
 * This is what a factory for input fields looks like.
 */
interface Factory
{
    public function field(): InputFactory;

    public function icon(): IconFactory;

    public function item(): ItemFactory;

    public function viewer(): ViewerFactory;
}
