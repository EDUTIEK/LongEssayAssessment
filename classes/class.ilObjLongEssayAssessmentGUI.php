<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use Edutiek\AssessmentService\Assessment\Api\ForClients as Assessment;
use Edutiek\AssessmentService\Assessment\Permissions;
use ILIAS\Plugin\LongEssayAssessment\Settings\OrgaSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI;
use ilGlobalTemplateInterface as Gti;
use ILIAS\UI\Component\Input\Container\Form\Standard as StandardForm;

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

        // Description is not shown by ilObjectPluginGUI
        if (isset($this->object)) {
            $this->assessment = $this->plugin->dic()->assessment($this->object->getAssId(), $this->object->getContextId(), $DIC->user()->getId());
            $this->permissions = $this->assessment->permissions();
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
        global $DIC;

        $this->tpl->setDescription($this->object->getDescription());
        $this->tpl->setTitleIcon('components/EDUTIEK/LongEssayAssessment/images/icon_xlas.svg');
        $alerts = [];
        if (!$this->assessment->orgaSettings()->get()->getOnline()) {
            $alert[] = [
                'property' => $this->plugin->txt('status'),
                'value' => $this->plugin->txt('offline')
            ];
        }
        $this->tpl->setAlertProperties($alerts);


        $next_class = $this->ctrl->getNextClass();
        if (!empty($next_class)) {
            switch ($next_class) {
                //                case 'illongessayassessmentuploadhandlergui':
                //                    // No permission check needed because it only stores temp files
                //                    $this->ctrl->forwardCommand(new ilLongEssayAssessmentUploadHandlerGUI(
                //                        $DIC->resourceStorage(),
                //                        new \ILIAS\Plugin\LongEssayAssessment\ilLongEssayAssessmentUploadTempFile(
                //                            $DIC->resourceStorage(),
                //                            $DIC->filesystem(),
                //                            $DIC->upload()
                //                        )
                //                    ));
                //                    break;
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
                    //                case 'ilias\plugin\longessayassessment\task\solutionsettingsgui':
                    //                    if ($this->permissions->canEditContentSettings()) {
                    //                        $this->activateTab('tab_task', 'tab_solution_settings');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\SolutionSettingsGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\task\resourcesadmingui':
                    //                    if ($this->permissions->canEditContentSettings()) {
                    //                        $this->activateTab('tab_task', 'tab_resources');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\ResourcesAdminGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\task\resourceuploadhandlergui':
                    //                    if ($this->permissions->canEditContentSettings()) {
                    //                        $task_repo = LongEssayAssessmentDI::getInstance()->getTaskRepo();
                    //                        $this->ctrl->forwardCommand(
                    //                            new \ILIAS\Plugin\LongEssayAssessment\Task\ResourceUploadHandlerGUI($DIC->resourceStorage(), $task_repo)
                    //                        );
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\task\editorsettingsgui':
                    //                    if ($this->permissions->canEditTechnicalSettings()) {
                    //                        $this->activateTab('tab_task', 'tab_technical_settings');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\EditorSettingsGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\task\correctionsettingsgui':
                    //                    if ($this->permissions->canEditTechnicalSettings()) {
                    //                        $this->activateTab('tab_task', 'tab_correction_settings');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\CorrectionSettingsGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\task\criteriaadmingui':
                    //                    if ($this->permissions->canEditContentSettings()) {
                    //                        $this->activateTab('tab_task', 'tab_criteria');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\CriteriaAdminGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\task\gradesadmingui':
                    //                    if ($this->permissions->canEditContentSettings()) {
                    //                        $this->activateTab('tab_task', 'tab_grades');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Task\GradesAdminGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\writer\writerstartgui':
                    //                    if ($this->permissions->canViewWriterScreen()) {
                    //                        $this->activateTab('tab_writer', 'tab_writer_start');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\writer\writerstatisticsgui':
                    //                    if ($this->permissions->canViewWriterStatistics()) {
                    //                        $this->activateTab('tab_writer', 'tab_writer_statistic');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\writer\writeruploadgui':
                    //                    if ($this->permissions->canViewWriterScreen()) {
                    //                        $this->activateTab('tab_writer', 'tab_writer_start');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Writer\WriterUploadGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\corrector\correctorstartgui':
                    //                    if ($this->permissions->canViewCorrectorScreen()) {
                    //                        $this->activateTab('tab_corrector', 'tab_corrector_start');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\Corrector\CorrectorStartGUI($this));
                    //                    }
                    //                    break;
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
                    //                case 'ilias\plugin\longessayassessment\writeradmin\writeradmingui':
                    //                    if ($this->permissions->canMaintainWriters()) {
                    //                        $this->activateTab('tab_writer_admin', 'tab_writer_admin');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\writeradmin\writeradminloggui':
                    //                    if ($this->permissions->canMaintainWriters()) {
                    //                        $this->activateTab('tab_writer_admin', 'tab_writer_admin_log');
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminLogGUI($this));
                    //                    }
                    //                    break;
                    //                case 'ilias\plugin\longessayassessment\correctoradmin\correctoradmingui':
                    //                    if ($this->permissions->canMaintainCorrectors()) {
                    //                        $cmd = $this->ctrl->getCmd('showStartPage');
                    //                        $active_sub = 'tab_correction_items';
                    //                        if(in_array($cmd, ["showCorrectors", "start", "performSearch"])) {
                    //                            $active_sub = 'tab_corrector_list';
                    //                        }
                    //                        $this->activateTab('tab_corrector_admin', $active_sub);
                    //                        $this->ctrl->forwardCommand(new \ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminGUI($this));
                    //                    }
                    //                    break;
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
        $inputs['multi_tasks'] = $this->ui_factory->input()->field()->checkbox(
            $this->plugin->txt('multi_tasks'),
            $this->plugin->txt('multi_tasks_info')
        );
        return $this->ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'save'),
            $inputs
        )->withSubmitLabel($this->plugin->txt($new_type . '_add'));
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

        // save the 'multi tasks' setting
        $assessment = $this->plugin->dic()->assessment($new_object->getAssId(), $new_object->getContextId(), $this->user->getId());
        $orga_settings = $assessment->orgaSettings()->get();
        $orga_settings->setMultiTasks(!empty($data['multi_tasks']));
        $assessment->orgaSettings()->save($orga_settings);

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
        //        if ($this->permissions->canEditContentSettings()) {
        //            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\task\solutionsettingsgui');
        //        }
        //        if ($this->permissions->canMaintainWriters()) {
        //            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\writerAdmin\writeradmingui');
        //        }
        //        if ($this->permissions->canMaintainCorrectors()) {
        //            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradmingui');
        //        }
        //        if ($this->permissions->canViewCorrectorScreen()) {
        //            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\corrector\correctorstartgui');
        //        }
        //        if ($this->permissions->canViewWriterScreen()) {
        //            $this->ctrl->redirectByClass('ilias\plugin\longessayassessment\writer\writerstartgui');
        //        }

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
        //        if ($this->permissions->canEditContentSettings()) {
        //            $tabs[] = [
        //                'id' => 'tab_solution_settings',
        //                'txt' => $this->plugin->txt('tab_solution_settings'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\solutionsettingsgui')
        //            ];
        //        }
        //        if ($this->permissions->canEditContentSettings()) {
        //            $tabs[] = [
        //                'id' => 'tab_resources',
        //                'txt' => $this->plugin->txt('tab_resources'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\resourcesadmingui')
        //            ];
        //        }
        //        if ($this->permissions->canEditTechnicalSettings()) {
        //            $tabs[] = [
        //                'id' => 'tab_technical_settings',
        //                'txt' => $this->plugin->txt('tab_technical_settings'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\editorsettingsgui')
        //            ];
        //        }
        //        if ($this->permissions->canEditTechnicalSettings()) {
        //            $tabs[] = [
        //                'id' => 'tab_correction_settings',
        //                'txt' => $this->plugin->txt('tab_correction_settings'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\correctionsettingsgui')
        //            ];
        //        }
        //
        //        if ($this->permissions->canEditContentSettings()) {
        //            $tabs[] = [
        //                'id' => 'tab_criteria',
        //                'txt' => $this->plugin->txt('tab_criteria'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\criteriaadmingui')
        //            ];
        //        }
        //        if ($this->permissions->canEditContentSettings()) {
        //            $tabs[] = [
        //                'id' => 'tab_grades',
        //                'txt' => $this->plugin->txt('tab_grades'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\task\gradesadmingui')
        //            ];
        //        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_assessment', $this->plugin->txt('tab_task'), $tabs[0]['url']);
            $this->subtabs['tab_assessment'] = $tabs;
        }

        // Corrector Tab
        $tabs = [];
        //        if ($this->permissions->canViewCorrectorScreen()) {
        //            $tabs[] = [
        //                'id' => 'tab_corrector_start',
        //                'txt' => $this->plugin->txt('tab_corrector_start'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\corrector\correctorstartgui')
        //            ];
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
        //        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_corrector', $this->plugin->txt('tab_corrector'), $tabs[0]['url']);
            $this->subtabs['tab_corrector'] = $tabs;
        }

        // Writer Tab
        $tabs = [];
        //        if ($this->permissions->canViewWriterScreen()) {
        //            $tabs[] = [
        //                'id' => 'tab_writer_start',
        //                'txt' => $this->plugin->txt('tab_writer_start'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writer\writerstartgui')
        //            ];
        //
        //        }
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
        //        if ($this->permissions->canMaintainWriters()) {
        //            $tabs[] = [
        //                'id' => 'tab_writer_admin',
        //                'txt' => $this->plugin->txt('tab_writer_admin'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writerAdmin\writeradmingui')
        //            ];
        //            $tabs[] = [
        //                'id' => 'tab_writer_admin_log',
        //                'txt' => $this->plugin->txt('tab_writer_admin_log'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\writerAdmin\writeradminloggui')
        //            ];
        //        }
        if (!empty($tabs)) {
            $this->tabs->addTab('tab_writer_admin', $this->plugin->txt('tab_writer_admin'), $tabs[0]['url']);
            $this->subtabs['tab_writer_admin'] = $tabs;
        }

        // Corrector Admin Tab
        $tabs = [];
        //        if ($this->permissions->canMaintainCorrectors()) {
        //            $tabs[] = [
        //                'id' => 'tab_correction_items',
        //                'txt' => $this->plugin->txt('tab_correction_items'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradmingui')
        //            ];
        //            $tabs[] = [
        //                'id' => 'tab_corrector_list',
        //                'txt' => $this->plugin->txt('tab_corrector_list'),
        //                'url' => $this->ctrl->getLinkTargetByClass('ilias\plugin\longessayassessment\correctorAdmin\correctoradmingui', "showCorrectors")
        //            ];
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
        //        }
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
