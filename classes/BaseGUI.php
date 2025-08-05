<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment;

use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\EssayTask\Data\HeadlineScheme;
use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;
use ilCtrl;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ILIAS\HTTP\Services as Http;
use ILIAS\Plugin\LongEssayAssessment\Common\Constraints\DataConstraints;
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;
use ILIAS\Plugin\LongEssayAssessment\Common\Session\SessionValues;
use ILIAS\Plugin\LongEssayAssessment\Provider\ToolProvider;
use ILIAS\Plugin\LongEssayAssessment\Settings\InstructionSettingsGUI;
use ILIAS\Plugin\LongEssayAssessment\UI\Factory as PluginUiFactory;
use ILIAS\Plugin\LongEssayAssessment\UI\UIService as PluginUIService;
use ILIAS\Refinery\Factory as RefineryFactory;
use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Component as UiComponent;
use ILIAS\UI\Factory as UiFactory;
use ILIAS\UI\Renderer;
use ilLanguage;
use ilLink;
use ilLongEssayAssessmentPlugin;
use ilMailFormCall;
use ilObjLongEssayAssessmentGUI;
use ilObjUser;
use ilTabsGUI;
use ilToolbarGUI;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use DateTimeInterface;
use ilDatePresentation;
use ilDateTime;
use Edutiek\AssessmentService\System\Format\FullService as SystemFormat;
use ILIAS\UI\Component\Input\Container\Form\Form;

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

    protected ilLongEssayAssessmentPlugin $plugin;
    protected SystemApi $system_api;
    protected AssessmentApi $assessment_api;
    protected EssayTaskApi $essay_task_api;
    protected TaskApi $task_api;
    protected PluginUiFactory $plugin_ui_factory;
    protected PluginUIService $plugin_ui_service;
    protected DataConstraints $constraints;

    protected RequestVariables $get;
    protected RequestVariables $post;

    protected ?TaskInfo $task_info;

    /** @var UiComponent[] */
    private array $components = [];
    private SessionValues $session;


    public function __construct(protected BaseObjectData $object)
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
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();

        $this->system_api = $this->plugin->dic()->system();
        $this->assessment_api = $this->plugin->dic()->assessment($this->object->getAssId(), $this->user->getId());
        $this->task_api = $this->plugin->dic()->task($this->object->getAssId(), $this->user->getId());
        $this->essay_task_api = $this->plugin->dic()->essayTask($this->object->getAssId(), $this->user->getId());

        $this->plugin_ui_factory = $this->plugin->dic()->uiFactory();
        $this->plugin_ui_service = $this->plugin->dic()->uiService();
        $this->constraints = $this->plugin->dic()->constraints();
        $this->session = $this->plugin->dic()->sessionValues(self::class, $this->object->getAssId());

        $this->get = new RequestVariables($DIC->http()->wrapper()->query(), $this->dic->refinery());
        $this->post = new RequestVariables($DIC->http()->wrapper()->post(), $this->dic->refinery());
    }

    /**
     * Add a component(s) to to be shown
     * @param UiComponent|UiComponent[] $component
     */
    protected function add(UiComponent|array $component): static
    {
        if (is_array($component)) {
            $this->components = array_merge($this->components, $component);
        } else {
            $this->components[] = $component;
        }
        return $this;
    }

    /**
     * Show the added components
     */
    protected function show(): void
    {
        $this->tpl->setContent($this->renderer->render($this->components));
    }

    protected function success(string $message, bool $keep = false): void
    {
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $message, $keep);
    }

    protected function failure(string $message, bool $keep = false): void
    {
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $message, $keep);
    }

    protected function info(string $message, bool $keep = false): void
    {
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $message, $keep);
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
     * Init the GUI to handle an assessment task
     * The task is identified by the query parameter task_id
     * Basic information of the task is loaded to the variable task_info
     */
    protected function initForTask()
    {
        $manager_service = $this->task_api->manager();

        $task_id = $this->get->integer('task_id', null) ?? (int) $this->session->get('task_id');
        $this->task_info = $manager_service->one($task_id) ?? $manager_service->first();

        if ($this->task_info === null) {
            $this->tpl->setContent('task not found');
            return;
        }

        $this->tpl->setTitle($this->object->getTitle() . ' | ' . $this->task_info->getTitle());
        $this->session->set('task_id', $this->task_info->getId());
        $this->ctrl->setParameter($this, 'task_id', $this->task_info->getId());

        if ($this->object->getMultiTasks()) {
            $tools_data = $this->dic->globalScreen()->tool()->context()->current()->getAdditionalData();
            $tools_data->add(ToolProvider::NAME, true);
            $tools_data->add(ToolProvider::GUI_CLASS, static::class);
        }
    }

    /**
     * Init the GUI to handle generic screens that are not bound to an assessment task
     */
    protected function initForNonTask()
    {
        if ($this->object->getMultiTasks()) {
            $tools_data = $this->dic->globalScreen()->tool()->context()->current()->getAdditionalData();
            $tools_data->add(ToolProvider::NAME, true);
            $tools_data->add(ToolProvider::GUI_CLASS, InstructionSettingsGUI::class);
        }
    }

    /**
     * Display an HTML text in readable width
     * @todo: merge with displayContent in a new UI element
     */
    public function displayText(?string $html): string
    {
        return '<div style="max-width: 60em;">' . $html . '</div>';
    }

    /**
     * Display an essay content
     * @todo: merge with displayText in a new UI element
     */
    public function displayContent(?string $html): string
    {
        $headline_class = "";
        if (!empty($settings = $this->essay_task_api->writingSettings()->get())) {
            switch ($settings->getHeadlineScheme()) {
                case HeadlineScheme::SINGLE:
                    $headline_class = "headlines-single";
                    break;
                case HeadlineScheme::THREE:
                    $headline_class = "headlines-three";
                    break;
                case HeadlineScheme::EDUTIEK:
                    $headline_class = "headlines-edutiek";
                    break;
                case HeadlineScheme::NUMERIC:
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
                case HeadlineScheme::SINGLE:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-single.css');
                    break;
                case HeadlineScheme::THREE:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-three.css');
                    break;
                case HeadlineScheme::EDUTIEK:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-edutiek.css');
                    break;
                case HeadlineScheme::NUMERIC:
                    $this->tpl->addCss($this->plugin->getDirectory() . '/templates/css/headlines-numeric.css');
                    break;
            }
        }
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

    /**
     * @param Component|Component[] $render_me
     */
    protected function renderContent($render_me): void
    {
        $this->tpl->setContent($this->renderer->render($render_me));
    }

    protected function withFormData(Form $form, callable $proc): Form
    {
        if ($this->request->getMethod() === 'POST') {
            $form = $form->withRequest($this->request);
            $data = $form->getData();
            if ($data) {
                $proc($data);
            }
        }
        return $form;
    }
}
