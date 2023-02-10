<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Implementation\Render\Loader;
use ILIAS\UI\Implementation\Render\RendererFactory;

class PluginLoader implements Loader
{
	/** @var string */
	private string $own_ns;
	private RendererFactory $plugin_renderer_factory;
	private Loader $fs_loader;

	public function __construct(
		Loader $fs_loader,
		RendererFactory $plugin_renderer_factory
	) {
		$this->own_ns = $this->getNamespace($this);
		$this->fs_loader = $fs_loader;
		$this->plugin_renderer_factory = $plugin_renderer_factory;
	}


	private function getNamespace(object $component): string{
		$class = get_class($component);
		return substr($class, 0, strrpos($class, '\\'));
	}

	public function getRendererFor(Component $component, array $contexts): \ILIAS\UI\Implementation\Render\ComponentRenderer
	{
		$ns = $this->getNamespace($component);

		if(str_starts_with($ns, $this->own_ns))
		{
			return $this->plugin_renderer_factory->getRendererInContext($component, $contexts);
		}

		return $this->fs_loader->getRendererFor($component, $contexts);
	}

	public function getRendererFactoryFor(Component $component): RendererFactory
	{
		$ns = $this->getNamespace($component);

		if(str_starts_with($ns, $this->own_ns))
		{
			return $this->plugin_renderer_factory;
		}

		return $this->fs_loader->getRendererFactoryFor($component);
	}

}