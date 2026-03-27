<?php

/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment;

use Edutiek\AssessmentService\Assessment\Api\ForClients as AssessmentApi;
use Edutiek\AssessmentService\Assessment\TaskInterfaces\TaskInfo;
use Edutiek\AssessmentService\EssayTask\Api\ForClients as EssayTaskApi;
use Edutiek\AssessmentService\System\Data\HeadlineScheme;
use Edutiek\AssessmentService\System\Api\ForClients as SystemApi;
use Edutiek\AssessmentService\Task\Api\ForClients as TaskApi;
use ilCtrl;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ILIAS\HTTP\Services as Http;
use ILIAS\Plugin\LongEssayAssessment\Common\Constraints\DataConstraints;
use ILIAS\Plugin\LongEssayAssessment\Common\Http\RequestVariables;
use ILIAS\Plugin\LongEssayAssessment\Common\Session\SessionValues;
use ILIAS\Plugin\LongEssayAssessment\ToolProvider;
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

    protected FixationGUI $fixation_gui;

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

        $this->get = new RequestVariables($this->dic->http()->wrapper()->query(), $this->dic->refinery());
        $this->post = new RequestVariables($this->dic->http()->wrapper()->post(), $this->dic->refinery());
        $this->fixation_gui = new FixationGUI($this->object);

        $manager_service = $this->task_api->manager();
        $task_id = $this->get->integer('task_id', null) ?? (int) $this->session->get('task_id');
        if ($manager_service->has($task_id)) {
            $this->task_info = $manager_service->one($task_id);
        } else {
            $this->task_info = $manager_service->first();
        }

        $this->addContentCss();
    }

    /**
     * Add component(s) to to be shown
     * @param UiComponent|UiComponent[]|null $component
     */
    protected function add(UiComponent|array|null $component): static
    {
        if (is_array($component)) {
            foreach ($component as $item) {
                $this->add($item);
            }
        } elseif ($component !== null) {
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

    protected function success(string $message, bool $keep = false, array $details = []): void
    {
        if (!empty($details)) {
            $message .= $this->renderer->render($this->ui_factory->listing()->unordered($details));
        }
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $message, $keep);
    }

    protected function failure(string $message, bool $keep = false, array $details = []): void
    {
        if (!empty($details)) {
            $message .= $this->renderer->render($this->ui_factory->listing()->unordered($details));
        }
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $message, $keep);
    }

    protected function info(string $message, bool $keep = false, array $details = []): void
    {
        if (!empty($details)) {
            $message .= $this->renderer->render($this->ui_factory->listing()->unordered($details));
        }
        $this->tpl->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_INFO, $message, $keep);
    }

    /**
     * Send a feedback message for an operation that affects multiple items
     * Some items may successfully be chenged, other not
     * @param string[] $changed
     * @param string[] $unchanged
     * @param string $success_txt
     * @param string $failed_txt
     * @return void
     */
    protected function multiFeedback(array $changed, array $unchanged, string $success_txt, string $failed_txt): void
    {
        $messages = [
            $this->plugin->txt(count($changed) ? $success_txt : $failed_txt),
        ];

        if (count($changed)) {
            $messages[] = $this->renderer->render($this->ui_factory->listing()->unordered($changed));
        }
        if (count($unchanged)) {
            if (count($changed)) {
                $messages[] = $this->plugin->txt('multi_feedback_unchanged');
            }
            $messages[] = $this->renderer->render($this->ui_factory->listing()->unordered($unchanged));
        }

        if (count($changed)) {
            $this->success(implode('<br>', $messages), true);
        } else {
            $this->failure(implode('<br>', $messages), true);
        }
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
     * Init the tools available for the GUI
     * @param $with_task_selection - show the tool for task selection, if possible
     *                               The task is identified by the query parameter task_id
     *                               Basic information of the task is loaded to the property task_info
     *
     * @param $with_task_selection - show the tool for fixations, if allowed
     */
    protected function initTools(bool $with_task_selection = false, bool $with_fixations = false): void
    {
        $tools_data = $this->dic->globalScreen()->tool()->context()->current()->getAdditionalData();
        $tools_data->add(ToolProvider::GUI_CLASS, static::class);

        if ($with_task_selection && $this->object->getMultiTasks()) {
            $this->session->set('task_id', $this->task_info?->getId() ?? '');
            $this->ctrl->setParameter($this, 'task_id', $this->task_info?->getId() ?? '');
            $this->tpl->setTitle($this->object->getTitle() . ' | ' . $this->task_info?->getTitle() ?? 'unknown task');

            $tools_data->add(ToolProvider::WITH_TASK_SELECTION, true);
        }

        if ($with_fixations && $this->assessment_api->permissions($this->object->getContextId())->canEditTemplates()) {
            $tools_data->add(ToolProvider::WITH_FIXATIONS, true);
        }
    }

    /**
     * Display an HTML text in readable width
     */
    public function displayText(?string $html): string
    {
        return $this->displayContent($html, HeadlineScheme::THREE);
    }

    /**
     * Display an essay content
     * @todo: merge with displayText in a new UI element
     */
    public function displayContent(?string $html, ?HeadlineScheme $scheme = null): string
    {
        $scheme = $scheme ?? $this->essay_task_api->writingSettings()->get()->getHeadlineScheme();
        $headline_class = $scheme->class();

        $html = $this->system_api->htmlProcessing()->secureContent($html);

        return '<div class="xlas-content ' . $headline_class
            . ' " style="max-width: 60em;">' . $html . '</div>';
    }

    /**
     * Add the css for displaying essay content
     * @todo add as a resource for a new UI element
     */
    private function addContentCss(): void
    {
        $this->tpl->addCss($this->plugin->asset('css/content.css'));
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
