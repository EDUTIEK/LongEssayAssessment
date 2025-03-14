<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Common;

use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use ilCtrl;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ILIAS\HTTP\Services as Http;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\EditorSettings;
use ILIAS\Plugin\LongEssayAssessment\UI\Implementation\Factory as PluginUiFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService as PluginUIService;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Modal\Modal;
use ILIAS\UI\Factory as UiFactory;
use ILIAS\UI\Renderer;
use ilLanguage;
use ilLink;
use ilLongEssayAssessmentPlugin;
use ilMailFormCall;
use ilObjLongEssayAssessment;
use ilObjLongEssayAssessmentGUI;
use ilObjUser;
use ilTabsGUI;
use ilToolbarGUI;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;

/**
 * Base class for GUI classes (except the plugin guis required by ILIAS)
 */
abstract class BaseGUI
{
    protected Container $dic;
    protected ilCtrl $ctrl;
    protected ilTabsGUI $tabs;
    protected ilGlobalTemplateInterface $tpl;
    protected ilLanguage $lng;
    protected ilToolbarGUI $toolbar;
    protected ilObjUser $user;
    protected UiFactory $ui_factory;
    protected Renderer $renderer;
    protected Http $http;
    /** @var RequestInterface|ServerRequestInterface */
    protected RequestInterface $request;
    protected RefineryFactory $refinery;

    protected ilObjLongEssayAssessment $object;
    protected ilLongEssayAssessmentPlugin $plugin;
    protected SystemApi $system_api;
    protected AssessmentApi $assessment_api;
    protected EssayTaskApi $essay_task_api;
    protected TaskApi $task_api;
    protected PluginUiFactory $plugin_ui_factory;
    protected PluginUIService $plugin_ui_service;

    /** @var Modal[] */
    private array $modals = [];

    /** @var array query params */
    protected array $params = [];


    public function __construct(ilObjLongEssayAssessment $object)
    {
        global $DIC;

        $this->dic = $DIC;
        $this->ctrl = $this->dic->ctrl();
        $this->tabs = $this->dic->tabs();
        $this->toolbar = $this->dic->toolbar();
        $this->user = $this->dic->user();
        $this->lng = $this->dic->language();
        $this->tpl = $this->dic->ui()->mainTemplate();
        $this->ui_factory = $this->dic->ui()->factory();
        $this->renderer = $this->dic->ui()->renderer();
        $this->http = $this->dic->http();
        $this->request = $this->dic->http()->request();
        $this->refinery = $this->dic->refinery();

        $this->object = $object;
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();

        $this->system_api = $this->plugin->dic()->system();
        $this->assessment_api = $this->plugin->dic()->assessment($this->object->getAssId(), $this->object->getContextId(), $this->user->getId());
        $this->task_api = $this->plugin->dic()->task($this->object->getAssId(), $this->user->getId());
        $this->essay_task_api = $this->plugin->dic()->essayTask($this->object->getAssId(), $this->user->getId());
        $this->plugin_ui_factory = $this->plugin->dic()->uiFactory();
        $this->plugin_ui_service = $this->plugin->dic()->uiService();

        $this->params = $this->request->getQueryParams();
    }

    /**
     * Raise a permission error
     * This may be needed if wrong ids for editing records are given
     */
    public function raisePermissionError()
    {
        $this->tpl->setOnScreenMessage("failure", $this->lng->txt("permission_denied"), true);
        $this->ctrl->clearParametersByClass(ilObjLongEssayAssessmentGUI::class);
        $this->ctrl->redirectByClass(ilObjLongEssayAssessmentGUI::class);
    }

    /**
     * Display an HTML text in readable width
     */
    public function displayText(?string $html): string
    {
        return '<div style="max-width: 60em;">' . $html . '</div>';
    }

    /**
     * Display an essay content
     */
    public function displayContent(?string $html): string
    {
        $headline_class = "";
        if (!empty($settings = $this->essay_task_api->writingSettings()->get())) {
            switch ($settings->getHeadlineScheme()) {
                case EditorSettings::HEADLINE_SCHEME_SINGLE:
                    $headline_class = "headlines-single";
                    break;
                case EditorSettings::HEADLINE_SCHEME_THREE:
                    $headline_class = "headlines-three";
                    break;
                case EditorSettings::HEADLINE_SCHEME_EDUTIEK:
                    $headline_class = "headlines-edutiek";
                    break;
                case EditorSettings::HEADLINE_SCHEME_NUMERIC:
                    $headline_class = "headlines-numeric";
                    break;
            }
        }
        return '<div class="long-essay-content ' . $headline_class . ' ">' . $html . '</div>';
    }

    /**
     * Add the css for displaying essay content
     */
    public function addContentCss(): void
    {
        $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/content.css');

        if (!empty($settings = $settings = $this->essay_task_api->writingSettings()->get())) {
            switch ($settings->getHeadlineScheme()) {
                case EditorSettings::HEADLINE_SCHEME_SINGLE:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-single.css');
                    break;
                case EditorSettings::HEADLINE_SCHEME_THREE:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-three.css');
                    break;
                case EditorSettings::HEADLINE_SCHEME_EDUTIEK:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-edutiek.css');
                    break;
                case EditorSettings::HEADLINE_SCHEME_NUMERIC:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-numeric.css');
                    break;
            }
        }
    }

    /**
     * Add Modals which gets rendered later by calling $this->setContent(html)
     *
     * @param Modal $modal
     * @return void
     */
    public function addModal(Modal $modal)
    {
        $this->modals[] = $modal;
    }

    protected function setContent($content)
    {
        $this->tpl->setContent($content . $this->renderer->render($this->modals));
    }

    /**
     * Open the mail form for sending a mail to accounts
     * @see Services/Mail/README.md
     */
    protected function openMailForm(array $logins, $current_command)
    {
        $sig = chr(13) . chr(10) . chr(13) . chr(10);
        $sig .= $this->plugin->txt('link_to_object');
        $sig .= chr(13) . chr(10);
        $sig .= ilLink::_getStaticLink((int) ($get['ref_id'] ?? ''));
        $sig = rawurlencode(base64_encode($sig));

        $get = $this->request->getQueryParams();
        $this->ctrl->redirectToUrl(
            ilMailFormCall::getRedirectTarget(
                $this,
                $current_command,
                ['ref_id' => $get['ref_id'] ?? ''],
                [
                    'type' => 'new', // Could also be 'reply' with an additional 'mail_id' paremter provided here
                    'rcp_to' => implode(', ', $logins),
                    ilMailFormCall::SIGNATURE_KEY => $sig
                ],
            )
        );
    }
}
