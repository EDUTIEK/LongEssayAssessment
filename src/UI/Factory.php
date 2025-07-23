<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\UI;

use ILIAS\Plugin\LongEssayAssessment\UI;
use ILIAS\Plugin\LongEssayAssessment\UI\Input\InputFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\IconFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Item\ItemFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Viewer\ViewerFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Statistic\StatisticFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Table\Factory as TableFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Protocol\Factory as ProtocolFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\Tree\TreeFactory;

/**
 * Class Factory
 *
 * @package ILIAS\Plugin\LongEssayAssessment\UI\Input
 */
class Factory
{
    public function __construct(
        private InputFactory $field_factory,
        private IconFactory  $icon_factory,
        private ItemFactory  $item_factory,
        private StatisticFactory $statistic_factory,
        private ViewerFactory $viewer_factory,
        private TableFactory $table_factory,
        private TreeFactory $tree_factory,
        private ProtocolFactory $protocol_factory
    ) {

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

    public function table(): TableFactory
    {
        return $this->table_factory;
    }

    public function tree(): TreeFactory
    {
        return $this->tree_factory;
    }

    public function protocol(): ProtocolFactory
    {
        return $this->protocol_factory;
    }
}
