<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\UI\Implementation\Component\Symbol\Icon\Factory as ILIASIconFactory;
use \ILIAS\Plugin\LongEssayAssessment\UI\Component\IconFactory as PluginIconFactory;
use ILIAS\UI\Implementation\Component\Symbol\Icon\Icon;

class IconFactory implements PluginIconFactory
{
	private ILIASIconFactory $factory;
	private \ilPlugin $plugin;
	public function __construct(ILIASIconFactory $factory, \ilPlugin $plugin)
	{
		$this->factory = $factory;
		$this->plugin = $plugin;
	}

	private function icon_path(string $name): string
	{
		return $this->plugin->getDirectory() . "/templates/images/icon_". $name . ".svg";
	}

	/**
	 * @inheritDoc
	 */
	public function xlas(string $label, string $size = 'small', bool $is_disabled = false): Icon
	{
		return $this->factory->custom($this->icon_path("xlas"), $label, $size, $is_disabled);
	}

	/**
	 * @inheritDoc
	 */
	public function appr(string $label, string $size = 'small', bool $is_disabled = false): Icon
	{
		return $this->factory->custom($this->icon_path("appr"), $label, $size, $is_disabled);
	}

	/**
	 * @inheritDoc
	 */
	public function disq(string $label, string $size = 'small', bool $is_disabled = false): Icon
	{
		return $this->factory->custom($this->icon_path("disq"), $label, $size, $is_disabled);
	}

	/**
	 * @inheritDoc
	 */
	public function time(string $label, string $size = 'small', bool $is_disabled = false): Icon
	{
		return $this->factory->custom($this->icon_path("time"), $label, $size, $is_disabled);
	}

	public function nots(string $label, string $size = 'small', bool $is_disabled = false): Icon
	{
		return $this->factory->custom($this->icon_path("nots"), $label, $size, $is_disabled);
	}

	public function nota(string $label, string $size = 'small', bool $is_disabled = false): Icon
	{
		return $this->factory->custom($this->icon_path("nota"), $label, $size, $is_disabled);
	}
}