<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\UI\Implementation;

use ILIAS\Plugin\LongEssayAssessment\UI;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\InputFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\IconFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\ItemFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\ViewerFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Tree\TreeFactory;

/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayAssessment\UI\Implementation
 */
class Factory implements UI\Component\Factory
{
    private InputFactory $field_factory;
    private IconFactory $icon_factory;
    private ItemFactory $item_factory;
    private StatisticFactory $statistic_factory;
    private ViewerFactory $viewer_factory;
    private TreeFactory $tree_factory;

    public function __construct(
        InputFactory $field_factory,
        IconFactory  $icon_factory,
        ItemFactory  $item_factory,
        StatisticFactory $statistic_factory,
        ViewerFactory $viewer_factory,
        TreeFactory $tree_factory
    ) {
        $this->field_factory = $field_factory;
        $this->icon_factory = $icon_factory;
        $this->item_factory = $item_factory;
        $this->statistic_factory = $statistic_factory;
        $this->viewer_factory = $viewer_factory;
        $this->tree_factory = $tree_factory;
    }


    public function field(): InputFactory
    {
        return $this->field_factory;
    }

    public function icon(): IconFactory
    {
        return $this->icon_factory;
    }

    public function item(): ItemFactory
    {
        return $this->item_factory;
    }

    public function statistic(): StatisticFactory
    {
        return $this->statistic_factory;
    }

    public function viewer(): ViewerFactory
    {
        return $this->viewer_factory;
    }

    public function tree(): TreeFactory
    {
        return $this->tree_factory;
    }
}
