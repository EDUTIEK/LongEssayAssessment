<?php

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\UI\Implementation\Render\ilTemplateWrapper;
use ILIAS\UI\Implementation\Render\TemplateFactory;

class PluginTemplateFactory implements TemplateFactory
{
	/**
	 * @var TemplateFactory
	 */
	private TemplateFactory $factory;

	/**
	 * @var \ilPlugin
	 */
	private \ilPlugin $plugin;

	/**
	 * @var	\ilGlobalTemplate
	 */
	protected \ilGlobalTemplateInterface $global_tpl;


	public function __construct(TemplateFactory $factory, \ilPlugin $plugin, \ilGlobalTemplateInterface $global_tpl)
	{
		$this->factory = $factory;
		$this->plugin = $plugin;
		$this->global_tpl = $global_tpl;
	}


	/**
	 * @inheritDoc
	 */
	public function getTemplate($path, $purge_unfilled_vars, $purge_unused_blocks) : \ILIAS\UI\Implementation\Render\Template
    {
		if(!str_starts_with($path, "src/UI/templates/"))
		{
			if(file_exists($this->plugin->getDirectory() . "/templates/" . $path))
			{
				$tpl = $this->plugin->getTemplate($path, $purge_unfilled_vars, $purge_unused_blocks);
				return new ilTemplateWrapper($this->global_tpl, $tpl);
			}
		}

		return $this->factory->getTemplate($path, $purge_unfilled_vars, $purge_unused_blocks);
	}
}