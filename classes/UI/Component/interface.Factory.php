<?php
namespace ILIAS\Plugin\LongEssayTask\UI\Component;

/**
 * This is what a factory for input fields looks like.
 */
interface Factory
{
	public function field(): FieldFactory;

	public function icon(): IconFactory;
}
