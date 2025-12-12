<?php

namespace ILIAS\Plugin\LongEssayAssessment\Collection;

use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\Plugin\LongEssayAssessment\GUI\Correction\CorrectionTableParent;

/**
 * Collection entrance point
 *
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Collection\CollectionGUI: ilLongEssayAssessmentDispatchGUI, ilObjPluginDispatchGUI
 */
class CollectionGUI
{
    private int $ref_id;
    private ?\ilObjCourse $object;
    private \ilCtrlInterface $ctrl;
    private \ilAccessHandler $access;
    private \ilGlobalTemplateInterface $tpl;
    private \ilToolbarGUI $toolbar;
    private \ilLocatorGUI $locator;
    private \ilLanguage $lng;
    private \ILIAS\UI\Factory $uiFactory;
    private \ILIAS\UI\Renderer $uiRenderer;
    private \ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper $query;
    private \ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper $post;
    private \ILIAS\Refinery\Factory $refinery;
    private \ilTabsGUI $tabs;
    private array $collection_node;
    private array $assessment_nodes;
    /**
     * @var \ilObjLongEssayAssessment[]
     */
    private array $plugin_objects;
    private \ilLongEssayAssessmentPlugin $plugin;
    private \ilTree $tree;
    private PluginDic $plugin_dic;
    private \ILIAS\DI\Container $dic;
    private \ilObjUser $user;

    public function __construct()
    {
        global $DIC;
        $this->ref_id = $DIC->http()->wrapper()->query()->retrieve('ref_id', $DIC->refinery()->kindlyTo()->int());
        $this->object = \ilObjectFactory::getInstanceByRefId($this->ref_id);
        $this->plugin = \ilLongEssayAssessmentPlugin::getInstance();

        $this->ctrl = $DIC->ctrl();
        $this->access = $DIC->access();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->toolbar = $DIC->toolbar();
        $this->locator = $DIC['ilLocator'];
        $this->lng = $DIC->language();
        $this->uiFactory = $DIC->ui()->factory();
        $this->uiRenderer = $DIC->ui()->renderer();
        $this->query = $DIC->http()->wrapper()->query();
        $this->post = $DIC->http()->wrapper()->post();
        $this->refinery = $DIC->refinery();
        $this->tabs = $DIC->tabs();
        $this->tree = $DIC->repositoryTree();
        $this->user = $DIC->user();
        $this->dic = $DIC;

        $this->init();
    }

    private function init()
    {
        $this->plugin_dic = PluginDIC::getInstance($this->dic, $this->plugin);
        $this->collection_node = $this->tree->getNodeData($this->object->getRefId());
        $this->assessment_nodes = $this->tree->getSubTree($this->collection_node, true, ['xlas']);
        array_filter($this->assessment_nodes, fn($x) => $this->access->checkAccess('read', '', $x['ref_id']));
        $this->plugin_objects = array_map(fn($x) => new \ilObjLongEssayAssessment($x['ref_id']), $this->assessment_nodes);
    }

    private function prepareOutput()
    {
        $type = $this->object->getType();
        $this->ctrl->setParameterByClass('ilObjCourseGUI', 'ref_id', $this->object->getRefId());
        $this->locator->addRepositoryItems($this->object->getRefId());
        #$this->locator->addItem($this->object->getTitle(),$this->ctrl->getLinkTargetByClass('ilObjTestGUI'));

        $this->tpl->setLocator();
        $this->tpl->setTitle(
            $this->refinery->encode()->htmlSpecialCharsAsEntities()->transform(
                $this->object->getPresentationTitle()
            )
        );
        $this->tpl->setDescription(
            $this->refinery->encode()->htmlSpecialCharsAsEntities()->transform(
                $this->object->getLongDescription()
            )
        );
        $this->tpl->setTitleIcon(\ilObject::_getIcon($this->object->getId(), 'big', $type), $this->lng->txt('obj_' . $type));

        $lgui = \ilObjectListGUIFactory::_getListGUIByType($this->object->getType());
        $lgui->initItem($this->object->getRefId(), $this->object->getId(), $this->object->getType());
        $this->tpl->setAlertProperties($lgui->getAlertProperties());
    }

    public function executeCommand(): void
    {
        $this->prepareOutput();
        $next_class = $this->ctrl->getNextClass($this);

        switch ($next_class) {

            default:
                $cmd = $this->ctrl->getCmd('list');
                switch ($cmd) {
                    case 'correctionStatus':
                    case 'list':
                        $this->$cmd();
                        $this->setTabs($cmd);
                        break;
                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }

        $this->tpl->printToStdout();
    }

    private function correctionStatus()
    {
        $multi = true;
        $corrections = 2;
        $ass_ids = array_map(fn(array $node) => $node['obj_id'], array_filter($this->assessment_nodes,
            fn(array $node) => $this->plugin_dic->assessment($node['obj_id'], $this->user->getId())->permissions($node['ref_id'])->canMaintainCorrectors()
        ));

        $this->ctrl->setParameter($this, 'ref_id', $this->object->getRefId());
        $table_parent = new CorrectionTableParent($this->dic, $this->plugin, $ass_ids, $this->ctrl->getFormAction($this, "correctionStatus"));
        $table_parent->setHasColumns(
            array_merge(
                ["image", "name", "login", "pseudonym", "location", "status", "assessment", "task", "writing_last_save",
                 "word_count", "pdf_version", "result", "points", "grade", "finalized", "finalized_from"],
                ...array_map(fn ($p) => ["corr_{$p}", "corr_{$p}_name", "corr_{$p}_status", "corr_{$p}_points", $multi ? "corr_{$p}_grade" : null, "corr_{$p}_authorized"], range(0, $corrections - 1))
            )
        )->setInitialVisibleColumns(["name", "login", "pseudonym", "location", "assessment", "task", "status", "writing_last_save", "word_count", "corr_1", "corr_2", "result"])
        ->setTableActions([$this->plugin_dic->uiFactory()->table()->action()->export('export', $this->lng->txt('export'), 'xlas_corrections_export.xlsx')]);

        $table_parent->setInitialVisibleColumns([]);
        $table = $this->plugin_dic->uiFactory()->table()->dataTable('correction_admin_table', $table_parent);
        $table->executeAction();
        $this->tpl->setContent($this->uiRenderer->render($table));
    }

    private function list()
    {
        $factory = $this->uiFactory;

        $items = [];

        foreach ($this->assessment_nodes as $node) {
            $this->ctrl->setParameterByClass(\ilObjLongEssayAssessmentGUI::class, 'ref_id', $node['ref_id']);
            $link = $this->ctrl->getLinkTargetByClass([\ilObjLongEssayAssessmentGUI::class]);

            $lgui = \ilObjectListGUIFactory::_getListGUIByType('xlas');
            $lgui->initItem($node['ref_id'], $node['obj_id'], 'xlas');

            $properties = [];
            foreach ($lgui->getProperties() as $prop) {
                $properties[$prop['property']] = isset($prop['link'])
                    ? $factory->button()->shy($prop['value'], $prop['link'])
                    : $prop['value'] ;
            }

            $items[] = $factory->item()->standard($factory->button()->shy($node['title'], $link))
                                       ->withProperties($properties)
                                       ->withLeadIcon($factory->symbol()->icon()->custom(\ilLongEssayAssessmentPlugin::_getIcon('xlas'), $this->plugin->txt("obj_xlas")))
                                       ->withDescription($node['description']);
        }

        $this->tpl->setContent($this->uiRenderer->render($factory->item()->group($this->plugin->txt('obj_xlas'), $items)));
    }

    private function setTabs(string $activate_tab)
    {
        $this->ctrl->setParameter($this, 'ref_id', $this->object->getRefId());

        $this->tabs->addSubTab('list', $this->plugin->txt('objs_xlas'), $this->ctrl->getLinkTarget($this, 'list'));
        if ($this->atleastOnePermission('ViewWriterStatistics')) {
            $this->tabs->addSubTab('statistic', $this->plugin->txt('statistic'), $this->ctrl->getLinkTarget($this, 'statistic'));
        }
        if ($this->atleastOnePermission('MaintainCorrectors')) {
            $this->tabs->addSubTab('correctionStatus', $this->plugin->txt('tab_correction_status'), $this->ctrl->getLinkTarget($this, 'correctionStatus'));
        }
        if ($this->atleastOnePermission('MaintainWriters')) {
            $this->tabs->addSubTab('writer_statistic', $this->plugin->txt('tab_writer_statistic'), $this->ctrl->getLinkTarget($this, 'writerStatistic'));
        }
        if ($this->atleastOnePermission('MaintainCorrectors')) {
            $this->tabs->addSubTab('corrector_statistic', $this->plugin->txt('tab_corrector_admin_statistic'), $this->ctrl->getLinkTarget($this, 'correctorStatistic'));
        }
        $this->tabs->activateSubTab($activate_tab);
    }

    private function atleastOnePermission($perm)
    {
        $func = "can" . $perm;

        foreach ($this->plugin_objects as $obj) {
            $has_permission = $this->plugin_dic->assessment($obj->getAssId(), $this->user->getId())->permissions($obj->getContextId())->$func();
            if ($has_permission) {
                return true;
            }
        }
        return false;
    }

    private function filterObjectsByPermission($perm)
    {
        $func = "can" . $perm;
        return array_filter(
            $this->plugin_objects,
            fn($obj) => $this->plugin_dic->assessment($obj->getAssId(), $this->user->getId())->permissions($obj->getContextId())->$func()
        );
    }

}
