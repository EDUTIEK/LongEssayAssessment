<?php

namespace ILIAS\Plugin\LongEssayAssessment\Collection;

use ILIAS\Plugin\LongEssayAssessment\Dependencies\PluginDic;
use ILIAS\Plugin\LongEssayAssessment\GUI\Correction\CorrectionTableParent;
use LTI\ilGlobalTemplate;

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
    private \ILIAS\Plugin\LongEssayAssessment\View\Data\CorrectionsViewRepo $corrections_view;

    public function __construct()
    {
        global $DIC;
        $this->ref_id = $DIC->http()->wrapper()->query()->retrieve('ref_id', $DIC->refinery()->kindlyTo()->int());
        $this->object = \ilObjectFactory::getInstanceByRefId($this->ref_id);
        $this->plugin = \ilLongEssayAssessmentPlugin::getInstance();
        $this->corrections_view = $this->plugin->dic()->view()->corrections();

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
        $this->plugin_dic = $this->plugin->dic();
        $this->collection_node = $this->tree->getNodeData($this->object->getRefId());
        $this->assessment_nodes = $this->tree->getSubTree($this->collection_node, true, ['xlas']);
        $this->plugin_objects = array_map(fn($x) => new \ilObjLongEssayAssessment($x['ref_id']), $this->assessment_nodes);
    }

    private function prepareOutput()
    {
        $type = $this->object->getType();
        $this->ctrl->setParameterByClass('ilObjCourseGUI', 'ref_id', $this->object->getRefId());
        $this->locator->addRepositoryItems($this->object->getRefId());

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
            //writing statistics for the writers
            case strtolower(CollectionWriterStatisticsGUI::class):
                $this->setTabs("statistic");
                $nodes = array_filter(
                    $this->assessment_nodes,
                    function (array $node) {
                        $permission = $this->plugin_dic->assessment($node['obj_id'], $this->user->getId())->permissions($node['ref_id']);
                        return $permission->canWriterViewWriterStatistics();
                    }
                );
                $this->ctrl->forwardCommand(new CollectionWriterStatisticsGUI($this->plugin, $this->plugin_dic, $this->dic, $nodes));
                break;

                // correction statistics for the correctors or correction admins
            case strtolower(CollectionCorrectorAdminStatisticsGUI::class):
                $this->setTabs("corrector_statistic");
                $nodes = array_filter(
                    $this->assessment_nodes,
                    function (array $node) {
                        $permission = $this->plugin_dic->assessment($node['obj_id'], $this->user->getId())->permissions($node['ref_id']);
                        return $permission->canMaintainCorrectors();
                    }
                );
                $this->ctrl->forwardCommand(new CollectionCorrectorAdminStatisticsGUI($this->plugin, $this->plugin_dic, $this->dic, $nodes, $this->object));
                break;

                // writing statistics for the correction admins
            case strtolower(CollectionWriterAdminStatisticsGUI::class):
                $this->setTabs("writer_statistic");
                $nodes = array_filter(
                    $this->assessment_nodes,
                    function (array $node) {
                        $permission = $this->plugin_dic->assessment($node['obj_id'], $this->user->getId())->permissions($node['ref_id']);
                        return $permission->canMaintainCorrectors();
                    }
                );
                $this->ctrl->forwardCommand(new CollectionWriterAdminStatisticsGUI($this->plugin, $this->plugin_dic, $this->dic, $nodes, $this->object));
                break;

            default:

                if ($this->ctrl->getCmd() == 'correctionStatus') {
                    $this->setTabs('correctionStatus');
                    $this->correctionStatus();

                } elseif (!empty($default_link = $this->setTabs())) {
                    $this->ctrl->redirectToURL($default_link);

                } else {
                    $this->tpl->setOnScreenMessage(\ilGlobalTemplateInterface::MESSAGE_TYPE_INFO, $this->plugin->txt('collections_not_available'));
                }
        }

        $this->tpl->printToStdout();
    }

    private function correctionStatus()
    {
        $ass_ids = array_map(fn(array $node) => $node['obj_id'], array_filter(
            $this->assessment_nodes,
            fn(array $node) => $this->plugin_dic->assessment($node['obj_id'], $this->user->getId())->permissions($node['ref_id'])->canMaintainCorrectors()
        ));

        // todo: improve handling of empty assessment list
        // quick fix: provide a pseudo id if no assessments should be shown
        // an empty array would result in a complete list of all assessments
        if (empty($ass_ids)) {
            $ass_ids = [0];
        }

        $multi = $this->corrections_view->hasMultiTasks($ass_ids);
        $corrections = $this->corrections_view->visibleCorrectors($ass_ids);

        $this->ctrl->setParameter($this, 'ref_id', $this->object->getRefId());
        $table_parent = new CorrectionTableParent(
            $this->dic,
            $this->plugin,
            $ass_ids,
            $this->ctrl->getFormAction($this, "correctionStatus")
        );
        $table_parent
            ->setHasColumns(
                array_merge(
                    ["image", "name", "login", "pseudonym", "location", "status", "assessment"],
                    $multi ? ["task"] : [],
                    ["writing_last_save", "word_count", "pdf_version", "result", "points", "grade", "finalized", "finalized_date", "finalized_name", "finalized_from_status"],
                    ...array_map(fn($p) => ["corr_{$p}", "corr_{$p}_name", "corr_{$p}_status", "corr_{$p}_points", $multi ? "corr_{$p}_grade" : null, "corr_{$p}_authorized"], range(0, $corrections - 1))
                )
            )->setInitialVisibleColumns(["name", "login", "pseudonym", "location", "assessment", "task", "status", "writing_last_save", "word_count", "corr_1", "corr_2"])
        ->setTableActions([$this->plugin_dic->uiFactory()->table()->action()->export(
            'export',
            $this->plugin->txt('table_export'),
            $this->object->getTitle() . ' - ' . $this->plugin->txt('correction_status_table_export_filename')
        )]);

        $table_parent->setInitialVisibleColumns([]);
        $table = $this->plugin_dic->uiFactory()->table()->dataTable('correction_status_table', $table_parent);
        $table->executeAction();
        $this->tpl->setContent($this->uiRenderer->render($table));
    }

    private function setTabs(string $activate_subtab = null): ?string
    {
        $this->ctrl->setParameter($this, 'ref_id', $this->object->getRefId());

        $default_link = null;

        if ($this->hasOnePermission('canMaintainCorrectors')) {
            $link = $this->ctrl->getLinkTarget($this, 'correctionStatus');
            $this->tabs->addSubTab('correctionStatus', $this->plugin->txt('tab_correction_status'), $link);
            $default_link ??= $link;
        }
        if ($this->hasOnePermission('canMaintainCorrectors')) {
            $link = $this->ctrl->getLinkTargetByClass([CollectionCorrectorAdminStatisticsGUI::class]);
            $this->tabs->addSubTab('corrector_statistic', $this->plugin->txt('tab_corrector_admin_statistic'), $link);
            $default_link ??= $link;
        }

        if ($this->hasOnePermission('canMaintainCorrectors')) {
            $link = $this->ctrl->getLinkTargetByClass([CollectionWriterAdminStatisticsGUI::class]);
            $this->tabs->addSubTab('writer_statistic', $this->plugin->txt('tab_writer_statistic'), $link);
            $default_link ??= $link;
        }

        if ($this->hasOnePermission('canWriterViewWriterStatistics')) {
            $link = $this->ctrl->getLinkTargetByClass([CollectionWriterStatisticsGUI::class]);
            $this->tabs->addSubTab('statistic', $this->plugin->txt('tab_statistic'), $link);
            $default_link ??= $link;
        }

        if ($activate_subtab) {
            $this->tabs->activateSubTab($activate_subtab);
        }

        return $default_link;
    }

    private function hasOnePermission($perm)
    {
        foreach ($this->plugin_objects as $obj) {
            $has_permission = $this->plugin_dic->assessment($obj->getAssId(), $this->user->getId())->permissions($obj->getContextId())->$perm();
            if ($has_permission) {
                return true;
            }
        }
        return false;
    }
}
