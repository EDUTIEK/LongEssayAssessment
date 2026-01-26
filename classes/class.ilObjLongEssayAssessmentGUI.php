<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use Edutiek\AssessmentService\Assessment\Api\ForClients as Assessment;
use Edutiek\AssessmentService\Assessment\Permissions;
use ILIAS\Plugin\LongEssayAssessment\Settings\CorrectionSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\OrgaSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI;
use ilGlobalTemplateInterface as Gti;
use ILIAS\Plugin\LongEssayAssessment\Settings\ResourcesAdminGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\SolutionSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\TechnicalSettingsGUI;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;
use ILIAS\Plugin\LongEssayAssessment\Settings\GradesAdminGUI;
use ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\CriteriaAdminGUI;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI;
use ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI;
use ILIAS\Plugin\LongEssayAssessment\Dashboard\ProtocolGUI;
use ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin\CorrectionAdminGUI;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorGUI;
use ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStartGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\DocumentationSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Dashboard\DashboardGUI;
use ILIAS\UI\Component\Input\Field\Radio;
use ILIAS\Plugin\LongEssayAssessment\FixationGUI;
use ILIAS\HTTP\Wrapper\ArrayBasedRequestWrapper;

/**
 * Plugin GUI Class
 * This is the entry point for the ILIAS controller
 * It delegates everything to specific gui classes
 *
 * @ilCtrl_isCalledBy ilObjLongEssayAssessmentGUI: ilRepositoryGUI, ilAdministrationGUI, ilLongEssayAssessmentDispatchGUI
 * @ilCtrl_Calls ilObjLongEssayAssessmentGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI, ilExportGUI
 */
class ilObjLongEssayAssessmentGUI extends ilObjectPluginGUI
{
    public const CMD_JUMP_TO_ORGA_SETTINGS = 'jumpToOrgaSettings';
    public const CMD_STANDARD = 'standardCommand';

    /** @var ilObjLongEssayAssessment */
    protected ?ilObject $object = null;

    /** @var ilLongEssayAssessmentPlugin */
    protected ?ilPlugin $plugin = null;

    private ilHelpGUI $help;
    private Permissions\ReadService $permissions;
    private Assessment $assessment;
    private ArrayBasedRequestWrapper $query;

    /**
     * Definition of the plugin specific sub tabs
     * @var array tab_id => [ ['id' => string, 'txt' => string, 'url' => string, ... ]
     * @see setTabs()
     */
    private $subtabs = [];


    /**
     * Redirection for goto links
     * Overrides standard function for plugins to use the own plugin dispatcher
     * Special treatment of a direct return from the writer or corrector web app
     */
    public static function _goto($a_target): void
    {
        global $DIC;


        $t = explode("_", $a_target[0]);
        $ref_id = (int) $t[0];

        if ($DIC->access()->checkAccess("read", "", $ref_id)) {
            if (isset($t[1])) {
                if ($t[1] == 'writer') {
                    $class_name = 'ilias\plugin\longessayassessment\writer\writerstartgui';
                }
                if ($t[1] == 'corrector') {
                    $class_name = 'ilias\plugin\longessayassessment\corrector\correctorstartgui';
                }
                if ($t[1] == 'correctoradmin') {
                    $class_name = 'ilias\plugin\longessayassessment\correctoradmin\correctoradmingui';
                }
                if (isset($class_name)) {
                    $DIC->ctrl()->setParameterByClass(self::class, "ref_id", $ref_id);
                    $DIC->ctrl()->setParameterByClass($class_name, "returned", '1');
                    $DIC->ctrl()->redirectByClass(array(ilLongEssayAssessmentDispatchGUI::class, self::class, $class_name), "");
                }
            }
        }

        // no read access or not a special return
        $DIC->ctrl()->setParameterByClass(self::class, "ref_id", $ref_id);
        $DIC->ctrl()->redirectByClass(array(ilLongEssayAssessmentDispatchGUI::class, self::class), self::CMD_STANDARD);
    }

    protected function afterConstructor(): void
    {
        global $DIC;

        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();

        $this->help = $DIC->help();

        if (isset($this->object)) {
            $this->assessment = $this->plugin->dic()->assessment($this->object->getAssId(), $DIC->user()->getId());
            $this->permissions = $this->assessment->permissions($this->object->getContextId());
            $this->query = $DIC->http()->wrapper()->query();

            $this->tpl->setDescription($this->object->getDescription());

            if (!$this->assessment->orgaSettings()->get()->getOnline()) {
                $this->tpl->setAlertProperties([[
                    'property' => $this->plugin->txt('status'),
                    'value' => $this->plugin->txt('offline')
                ]]);
            }
        }
    }

    final public function getType(): string
    {
        return ilLongEssayAssessmentPlugin::ID;
    }

    public function getObject(): ?ilObjLongEssayAssessment
    {
        return $this->object;
    }

    /**
     * Handles all commands of this class, centralizes permission checks
     */
    public function performCommand($cmd): void
    {
        $next_class = $this->ctrl->getNextClass();
        if (!empty($next_class)) {
            switch ($next_class) {
                case strtolower(ilLongEssayAssessmentUploadHandlerGUI::class):
                    if ($this->permissions->canUploadFiles()) {
                        $this->ctrl->forwardCommand(new ilLongEssayAssessmentUploadHandlerGUI(
                            $this->plugin->dic()->system()->fileStorage(),
                            $this->plugin->dic()->uploadTempFile()
                        ));
                    }
                    break;
                case strtolower(OrgaSettingsGUI::class):
                    if ($this->permissions->canEditOrgaSettings()) {
                        $this->activateTab('tab_assessment', 'tab_orga_settings');
                        $this->ctrl->forwardCommand(new OrgaSettingsGUI($this->object));
                    }
                    break;
                case strtolower(InstructionSettingsGUI::class):
                    if ($this->permissions->canEditContentSettings()) {
                        $this->activateTab('tab_assessment', 'tab_instructions_settings');
                        $this->ctrl->forwardCommand(new InstructionSettingsGUI($this->object));
                    }
                    break;
                case strtolower(SolutionSettingsGUI::class):
                    if ($this->permissions->canEditContentSettings()) {
                        $this->activateTab('tab_assessment', 'tab_solution_settings');
                        $this->ctrl->forwardCommand(new SolutionSettingsGUI($this->object));
                    }
                    break;
                case strtolower(GradesAdminGUI::class):
                    if ($this->permissions->canEditGrades()) {
                        $this->activateTab('tab_assessment', 'tab_grades');
                        $this->ctrl->forwardCommand(new GradesAdminGUI($this->object));
                    }
                    break;
                case strtolower(CriteriaAdminGUI::class):
                    if ($this->permissions->canEditContentSettings()) { # TODO: Das muss anders
                        $this->activateTab('tab_assessment', 'tab_criteria');
                        $this->ctrl->forwardCommand(new CriteriaAdminGUI($this->object));
                    }
                    break;
                case strtolower(ResourcesAdminGUI::class):
                    if ($this->permissions->canEditContentSettings()) {
                        $this->activateTab('tab_assessment', 'tab_resources');
                        $this->ctrl->forwardCommand(new ResourcesAdminGUI($this->object));
                    }
                    break;
                case strtolower(TechnicalSettingsGUI::class):
                    if ($this->permissions->canEditTechnicalSettings()) {
                        $this->activateTab('tab_assessment', 'tab_technical_settings');
                        $this->ctrl->forwardCommand(new TechnicalSettingsGUI($this->object));
                    }
                    break;
                case strtolower(DocumentationSettingsGUI::class):
                    if ($this->permissions->canEditDocumentationSettings()) {
                        $this->activateTab('tab_assessment', 'tab_documentation_settings');
                        $this->ctrl->forwardCommand(new DocumentationSettingsGUI($this->object));
                    }
                    break;
                case strtolower(CorrectionSettingsGUI::class):
                    if ($this->permissions->canEditTechnicalSettings()) {
                        $this->activateTab('tab_assessment', 'tab_correction_settings');
                        $this->ctrl->forwardCommand(new CorrectionSettingsGUI($this->object));
                    }
                    break;
                case strtolower(WriterStartGUI::class):
                    if ($this->permissions->canViewWriterScreen()) {
                        $this->activateTab('tab_writer', 'tab_writer_start');
                        $this->ctrl->forwardCommand(new WriterStartGUI($this->object));
                    }
                    break;
                    //                case 'ilias\plugin\longessayassessment\writer\writerstatisticsgui':
                    //                    if ($this->permissions->canViewWriterStatistics()) {
                    //                        $this->activateTab('tab_writer', 'tab_writer_statistic');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI($this));
                    //                    }
                    //                    break;
                case strtolower(WriterUploadGUI::class):
                    if ($this->query->has('writer_id') && $this->permissions->canMaintainWriters()) {
                        $this->activateTab('tab_writer_admin');
                        $this->ctrl->forwardCommand(new WriterUploadGUI($this->object));
                        break;
                    }
                    if ($this->permissions->canViewWriterScreen()) {
                        $this->activateTab('tab_writer', 'tab_writer_start');
                        $this->ctrl->forwardCommand(new WriterUploadGUI($this->object));
                    }
                    break;
                case strtolower(CorrectorStartGUI::class):
                    if ($this->permissions->canViewCorrectorScreen()) {
                        $this->activateTab('tab_corrector', 'tab_corrector_start');
                        $this->ctrl->forwardCommand(new CorrectorStartGUI($this->object));
                    }
                    break;
                    //                case 'ilias\plugin\longessayassessment\corrector\correctorcriteriagui':
                    //                    if ($this->permissions->canViewCorrectorScreen()) {
                    //                        $this->activateTab('tab_corrector', 'tab_corrector_criteria');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorCriteriaGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\corrector\correctionreportgui':
                    //                    if ($this->permissions->canWriteCorrectionReport()) {
                    //                        $this->activateTab('tab_corrector', 'tab_correction_report');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectionReportGUI($this));
                    //                    }
                    //                    break;
                case strtolower(DashboardGUI::class):
                    if ($this->permissions->canViewDashboard()) {
                        $this->activateTab('tab_dashboard', 'tab_dashboard');
                        $this->ctrl->forwardCommand(new DashboardGUI($this->object));
                    }
                    break;
                case strtolower(WriterAdminGUI::class):
                    if ($this->permissions->canMaintainWriters()) {
                        $this->activateTab('tab_writer_admin', 'tab_writer_admin');
                        $this->ctrl->forwardCommand(new WriterAdminGUI($this->object));
                    }
                    break;
                case strtolower(ProtocolGUI::class):
                    if ($this->permissions->canMaintainWriters()) {
                        $this->activateTab('tab_dashboard', 'tab_dashboard_log');
                        $this->ctrl->forwardCommand(new ProtocolGUI($this->object));
                    }
                    break;
                case strtolower(CorrectionAdminGUI::class):
                    if ($this->permissions->canMaintainCorrectors()) {
                        $this->activateTab('tab_corrector_admin', 'tab_correction_items');
                        $this->ctrl->forwardCommand(new CorrectionAdminGUI($this->object));
                    }
                    break;
                case strtolower(CorrectorGUI::class):
                    if ($this->permissions->canMaintainWriters()) {
                        $this->activateTab('tab_corrector_admin', 'tab_corrector_list');
                        $this->ctrl->forwardCommand(new CorrectorGUI($this->object));
                    }
                    break;
                case strtolower(FixationGUI::class):
                    if ($this->permissions->canEditTemplates()) {
                        $this->ctrl->forwardCommand(new FixationGUI($this->object));
                    }
                    break;

                    //                case 'ilias\plugin\longessayassessment\correctoradmin\correctoradminstatisticsgui':
                    //                    if ($this->permissions->canMaintainCorrectors()) {
                    //                        $cmd = $this->ctrl->getCmd('showStartPage');
                    //                        $active_sub = 'tab_corrector_adm_statistic';
                    //                        $this->activateTab('tab_corrector_admin', $active_sub);
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminStatisticsGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\correctoradmin\correctoradminwriterstatisticsgui':
                    //                    if ($this->permissions->canMaintainCorrectors()) {
                    //                        $cmd = $this->ctrl->getCmd('showStartPage');
                    //                        $active_sub = 'tab_writer_statistic';
                    //                        $this->activateTab('tab_corrector_admin', $active_sub);
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminWriterStatisticsGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\corrector\correctorstatisticsgui':
                    //                    if ($this->permissions->canViewCorrectorScreen()) {
                    //                        $cmd = $this->ctrl->getCmd('showStartPage');
                    //                        $active_sub = 'tab_corrector_statistic';
                    //                        $this->activateTab('tab_corrector', $active_sub);
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStatisticsGUI($this));
                    //                    }
                    //                    break;
                default:
                    $this->tpl->setOnScreenMessage(Gti::MESSAGE_TYPE_FAILURE, 'Unsupported cmdClass: ' . $next_class, true);
            }
        } else {
            switch ($cmd) {
                case self::CMD_JUMP_TO_ORGA_SETTINGS:
                    $this->checkPermission("write");
                    $this->$cmd();
                    break;

                    // list all commands that need read permission here
                case self::CMD_STANDARD:
                    $this->$cmd();
                    break;

                default:
                    $this->tpl->setOnScreenMessage(Gti::MESSAGE_TYPE_FAILURE, 'Unsupported cmd: ' . $cmd);
            }
        }
    }

    protected function initCreateForm(string $new_type): StandardForm
    {
        $form = parent::initCreateForm($new_type);
        $inputs = $form->getInputs();
        $txt = $this->plugin->txt(...);

        $templates = $this->templates();
        $switches = [];

        if (count($templates)) {
            $switches['ref'] = $this->ui_factory->input()->field()->group([
                'id' => array_reduce(
                    $this->templates(),
                    fn(Radio $r, array $o) => $r->withOption(...$o),
                    $this->ui_factory->input()->field()->radio($txt('template'))
                ),
            ], $txt('use_template'));
            $default_value = ['ref', ['id' => $templates[0][0]]];
        } else {
            $default_value = ['tasks', ['amount' => 'single']];
        }

        $switches['tasks'] = $this->ui_factory->input()->field()->group([
            'amount' => $this->ui_factory->input()->field()->radio($txt('task_type'))
                ->withOption('single', $txt('single_task'), $txt('single_task_info'))
                ->withOption('multiple', $txt('multi_tasks'), $txt('multi_tasks_info'))
            ,
        ], $txt('use_standard'))->withValue(['amount' => 'single']);

        $inputs['template'] = $this->ui_factory->input()->field()->switchableGroup($switches, $txt('predefined_settings'))
            ->withValue($default_value);

        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'save'),
            $inputs
        )->withSubmitLabel($txt($new_type . '_add'));
    }

    private function templates(): array
    {
        global $DIC;
        $node_path = $this->tree->getNodeTreeData($this->parent_id)['path'];

        $r = $DIC->database()->queryF(
            'SELECT ref_id FROM xlas_as_orga_settings JOIN object_reference ON ass_id = obj_id WHERE template = 1 AND ref_id IN (SELECT child FROM tree WHERE deleted IS NULL AND parent IN (SELECT child FROM tree WHERE %s LIKE CONCAT(path, ".%%") OR child = %s))',
            [ilDBConstants::T_TEXT, ilDBConstants::T_INTEGER],
            [$node_path, $this->parent_id]
        );

        $templates = [];
        while ($row = $DIC->database()->fetchAssoc($r)) {
            $obj_id = ilObject::_lookupObjId($row['ref_id']);
            $templates[] = [$row['ref_id'], ilObject::_lookupTitle($obj_id), ilObject::_lookupDescription($obj_id)];
        }
        return $templates;
    }

    public function save(): void
    {
        $form = $this
            ->initCreateForm($this->requested_new_type)
            ->withRequest($this->request);
        $data = $form->getData();

        // set the template for creating the new object
        $template_ref = $data['template'][1]['id'] ?? null;
        if (($data['template'][0] ?? null) === 'ref' && $template_ref && is_numeric($template_ref)) {
            $template = new ilObjLongEssayAssessment((int) $template_ref);
            ilObjLongEssayAssessment::setCreateTemplate($template);
        }
        if (($data['template'][1]['amount'] ?? null) === 'multiple') {
            ilObjLongEssayAssessment::setCreateMultiTasks(true);
        }

        parent::save();
    }

    /**
     * Redirect after a new object is saves
     * Here: use illongessayassessmentdispatchgui instead of ilobjplugindispatchgui
     * @param ilObjLongEssayAssessment $new_object
     */
    protected function afterSave(ilObject $new_object): void
    {
        $form = $this
            ->initCreateForm($this->requested_new_type)
            ->withRequest($this->request);
        $data = $form->getData();

        if (($data['template'][0] ?? null) === 'ref') {
            $template = ilObjLongEssayAssessment::getCreateTemplate();
            $assessment = $this->plugin->dic()->assessment($new_object->getAssId(), $this->user->getId());
            $orga_settings = $assessment->orgaSettings()->get();
            $orga_settings->setOnline(false);
            $orga_settings->setTemplate(false);
            $orga_settings->setSrcTemplateName($template?->getTitle());
            $assessment->orgaSettings()->save($orga_settings);
        }

        // always send a message
        $this->tpl->setOnScreenMessage(Gti::MESSAGE_TYPE_SUCCESS, $this->lng->txt("object_added"), true);

        $this->ctrl->setTargetScript('ilias.php');
        $this->ctrl->setParameterByClass(self::class, "ref_id", $new_object->getRefId());
        $this->ctrl->redirectByClass([ilLongEssayAssessmentDispatchGUI::class, self::class], $this->getAfterCreationCmd());
    }

    /**
     * After object has been created -> jump to this command
     */
    public function getAfterCreationCmd(): string
    {
        return self::CMD_JUMP_TO_ORGA_SETTINGS;
    }

    /**
     * Get standard command
     */
    public function getStandardCmd(): string
    {
        return self::CMD_STANDARD;
    }

    /**
     * Apply the standard command
     */
    protected function standardCommand()
    {
        if ($this->permissions->canEditOrgaSettings()) {
            $this->ctrl->redirectByClass(OrgaSettingsGUI::class);
        }
        if ($this->permissions->canEditContentSettings()) {
            $this->ctrl->redirectByClass(InstructionSettingsGUI::class);
        }
        if ($this->permissions->canMaintainWriters()) {
            $this->ctrl->redirectByClass(WriterAdminGUI::class);
        }
        if ($this->permissions->canMaintainCorrectors()) {
            $this->ctrl->redirectByClass(CorrectionAdminGUI::class);
        }
        if ($this->permissions->canViewCorrectorScreen()) {
            $this->ctrl->redirectByClass(CorrectorStartGUI::class);
        }
        if ($this->permissions->canViewWriterScreen()) {
            $this->ctrl->redirectByClass(WriterStartGUI::class);
        }

        $this->tpl->setOnScreenMessage(Gti::MESSAGE_TYPE_FAILURE, $this->plugin->txt('message_no_admin_writer_corrector'), true);
    }

    /**
     * Jump to the editing of organisational settings (used in actions menu)
     */
    protected function jumpToOrgaSettings()
    {
        $this->ctrl->redirectByClass(OrgaSettingsGUI::class);
    }

    /**
     * Set tabs (called already by ilObjPluginGUI before performCommand is called)
     * This defines the available sub tabs for each tab, based on the permissions
     * A Tab is added to the GUI with the URL of the first available sub tab
     * The actual sub tabs are added to the GUI in self::activateTab() when the current tab is known
     */
    public function setTabs(): void
    {
        $this->help->setScreenIdComponent($this->getPlugin()->getId());

        $this->subtabs = [];

        // Assessment Definition Tab
        $tabs = [];
        if ($this->permissions->canEditOrgaSettings()) {
            $tabs[] = [
                'id' => 'tab_orga_settings',
                'txt' => $this->plugin->txt('tab_orga_settings'),
                'url' => $this->ctrl->getLinkTargetByClass(OrgaSettingsGUI::class)
            ];
        }
        if ($this->permissions->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_instructions_settings',
                'txt' => $this->plugin->txt('tab_instructions_settings'),
                'url' => $this->ctrl->getLinkTargetByClass(InstructionSettingsGUI::class)
            ];
        }
        if ($this->permissions->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_solution_settings',
                'txt' => $this->plugin->txt('tab_solution_settings'),
                'url' => $this->ctrl->getLinkTargetByClass(SolutionSettingsGUI::class)
            ];
        }
        if ($this->permissions->canEditContentSettings()) {
            $tabs[] = [
                'id' => 'tab_resources',
                'txt' => $this->plugin->txt('tab_resources'),
                'url' => $this->ctrl->getLinkTargetByClass(ResourcesAdminGUI::class)
            ];
        }
        if ($this->permissions->canEditTechnicalSettings()) {
            $tabs[] = [
                'id' => 'tab_technical_settings',
                'txt' => $this->plugin->txt('tab_technical_settings'),
                'url' => $this->ctrl->getLinkTargetByClass(TechnicalSettingsGUI::class)
            ];
        }
        if ($this->permissions->canEditTechnicalSettings()) {
            $tabs[] = [
                'id' => 'tab_correction_settings',
                'txt' => $this->plugin->txt('tab_correction_settings'),
                'url' => $this->ctrl->getLinkTargetByClass(CorrectionSettingsGUI::class)
            ];
        }
        if ($this->permissions->canEditOrgaSettings()) { # TODO: Own permission for the criteria tab?
            $tabs[] = [
                'id' => 'tab_criteria',
                'txt' => $this->plugin->txt('tab_criteria'),
                'url' => $this->ctrl->getLinkTargetByClass(CriteriaAdminGUI::class)
            ];
        }
        if ($this->permissions->canEditGrades()) {
            $tabs[] = [
                'id' => 'tab_grades',
                'txt' => $this->plugin->txt('tab_grades'),
                'url' => $this->ctrl->getLinkTargetByClass(GradesAdminGUI::class)
            ];
        }
        if ($this->permissions->canEditDocumentationSettings()) {
            $tabs[] = [
                'id' => 'tab_documentation_settings',
                'txt' => $this->plugin->txt('tab_documentation_settings'),
                'url' => $this->ctrl->getLinkTargetByClass(DocumentationSettingsGUI::class)
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_assessment', $this->plugin->txt('tab_task'), $tabs[0]['url']);
            $this->subtabs['tab_assessment'] = $tabs;
        }

        // Corrector Tab
        $tabs = [];
        if ($this->permissions->canViewCorrectorScreen()) {
            $tabs[] = [
                'id' => 'tab_corrector_start',
                'txt' => $this->plugin->txt('tab_corrector_start'),
                'url' => $this->ctrl->getLinkTargetByClass(strtolower(CorrectorStartGUI::class))
            ];
            //            if($this->permissions->canViewCorrectorScreen()) {
            //                $tabs[] = [
            //                    'id' => 'tab_corrector_criteria',
            //                    'txt' => $this->plugin->txt('tab_criteria'),
            //                    'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctorcriteriagui')
            //                ];
            //            }
            //            $tabs[] = [
            //                'id' => 'tab_corrector_statistic',
            //                'txt' => $this->plugin->txt('tab_corrector_statistic'),
            //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctorstatisticsgui')
            //            ];
            //        }
            //        if ($this->permissions->canWriteCorrectionReport()) {
            //            $tabs[] = [
            //                'id' => 'tab_correction_report',
            //                'txt' => $this->plugin->txt('tab_correction_report'),
            //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctionreportgui')
            //            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector', $this->plugin->txt('tab_corrector'), $tabs[0]['url']);
            $this->subtabs['tab_corrector'] = $tabs;
        }

        // Writer Tab
        $tabs = [];
        if ($this->permissions->canViewWriterScreen()) {
            $tabs[] = [
                'id' => 'tab_writer_start',
                'txt' => $this->plugin->txt('tab_writer_start'),
                'url' => $this->ctrl->getLinkTargetByClass(WriterStartGUI::class),
            ];

        }
        //        if ($this->permissions->canViewWriterStatistics()) {
        //            $tabs[] = [
        //                'id' => 'tab_writer_statistic',
        //                'txt' => $this->plugin->txt('tab_statistic'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writer\writerstatisticsgui')
        //            ];
        //
        //        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_writer', $this->plugin->txt('tab_writer'), $tabs[0]['url']);
            $this->subtabs['tab_writer'] = $tabs;
        }


        // Writer Admin Tab
        $tabs = [];
        if ($this->permissions->canViewDashboard()) {
            $tabs[] = [
                'id' => 'tab_dashboard',
                'txt' => $this->plugin->txt('tab_dashboard'),
                'url' => $this->ctrl->getLinkTargetByClass(strtolower(DashboardGUI::class))
            ];
            $tabs[] = [
                'id' => 'tab_dashboard_log',
                'txt' => $this->plugin->txt('tab_writer_admin_log'),
                'url' => $this->ctrl->getLinkTargetByClass(strtolower(ProtocolGUI::class))
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_dashboard', $this->plugin->txt('tab_dashboard'), $tabs[0]['url']);
            $this->subtabs['tab_dashboard'] = $tabs;
        }

        // Writer Admin Tab
        $tabs = [];
        if ($this->permissions->canMaintainWriters()) {
            $tabs[] = [
                'id' => 'tab_writer_admin',
                'txt' => $this->plugin->txt('tab_writer_admin'),
                'url' => $this->ctrl->getLinkTargetByClass(strtolower(WriterAdminGUI::class))
            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_writer_admin', $this->plugin->txt('tab_writer_admin'), $tabs[0]['url']);
            $this->subtabs['tab_writer_admin'] = $tabs;
        }

        // Corrector Admin Tab
        $tabs = [];
        if ($this->permissions->canMaintainCorrectors()) {
            $tabs[] = [
                'id' => 'tab_correction_items',
                'txt' => $this->plugin->txt('tab_correction_items'),
                'url' => $this->ctrl->getLinkTargetByClass(strtolower(CorrectionAdminGUI::class))
            ];
            $tabs[] = [
                'id' => 'tab_corrector_list',
                'txt' => $this->plugin->txt('tab_corrector_list'),
                'url' => $this->ctrl->getLinkTargetByClass(strtolower(CorrectorGUI::class), "showItems")
            ];
            //            $tabs[] = [
            //                'id' => 'tab_corrector_adm_statistic',
            //                'txt' => $this->plugin->txt('tab_corrector_admin_statistic'),
            //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradminstatisticsgui', "showStartPage")
            //            ];
            //            $tabs[] = [
            //                'id' => 'tab_writer_statistic',
            //                'txt' => $this->plugin->txt('tab_writer_statistic'),
            //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradminwriterstatisticsgui', "showStartPage")
            //            ];
        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector_admin', $this->plugin->txt('tab_corrector_admin'), $tabs[0]['url']);
            $this->subtabs['tab_corrector_admin'] = $tabs;
        }

        // standard info screen tab
        if ($this->permissions->canViewInfoScreen()) {
            $this->addInfoTab();
        }

        // standard export tab
        $this->addExportTab();

        // standard permission tab
        $this->addPermissionTab();

        // activate tab for some external GUIs
        $next_class = $this->ctrl->getCmdClass();
        switch ($next_class) {
            case strtolower(ilExportGUI::class):
                $this->tabs->activateTab("export");
                break;
        }
    }

    /**
     * Activate a tab, add its sub tabs and activate a sub tab
     */
    protected function activateTab(string $a_tab_id, string $a_subtab_id = '')
    {

        $this->tabs->activateTab($a_tab_id);

        if (!empty($this->subtabs[$a_tab_id])) {
            foreach ($this->subtabs[$a_tab_id] as $subtab) {
                $this->tabs->addSubTab($subtab['id'], $subtab['txt'], $subtab['url']);
            }
            $this->help->setScreenId(str_replace("tab_", "", $a_tab_id));
        }

        if (!empty($a_subtab_id)) {
            $this->tabs->activateSubTab($a_subtab_id);
            $this->help->setSubScreenId(str_replace("tab_", "", $a_subtab_id));
        }
    }
}
