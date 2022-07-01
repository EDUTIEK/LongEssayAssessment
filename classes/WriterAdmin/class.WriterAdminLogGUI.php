<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\WriterAdmin;

use ILIAS\Plugin\LongEssayTask\BaseGUI;
use ILIAS\Plugin\LongEssayTask\Data\LogEntry;
use ILIAS\Plugin\LongEssayTask\Data\WriterNotice;
use ILIAS\UI\Factory;
use \ilUtil;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayTask\WriterAdmin
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayTask\WriterAdmin\WriterAdminLogGUI: ilObjLongEssayTaskGUI
 */
class WriterAdminLogGUI extends BaseGUI
{
    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $cmd = $this->ctrl->getCmd('showStartPage');
        switch ($cmd)
        {
            case 'showStartPage':
                $this->$cmd();
                break;

            default:
                $this->tpl->setContent('unknown command: ' . $cmd);
        }
    }


    /**
     * Show the items
     */
    protected function showStartPage()
    {
        $modal1 = $this->uiFactory->modal()->roundtrip('Notiz hinzufügen', [
            $this->uiFactory->input()->field()->text('Titel')->withRequired(true),
            $this->uiFactory->input()->field()->textarea('Text')->withRequired(true)
        ])->withActionButtons([$this->uiFactory->button()->primary('Hinzufügen','#')]);
        $button1 = $this->uiFactory->button()->standard('Notiz hinzufügen', '#')
            ->withOnClick($modal1->getShowSignal());
        $this->toolbar->addComponent($button1);

        $modal2 = $this->uiFactory->modal()->roundtrip('Benachrichtigung senden', [
            $this->uiFactory->input()->field()->text('Titel')->withRequired(true),
            $this->uiFactory->input()->field()->textarea('Text')->withRequired(true)
        ])->withActionButtons([$this->uiFactory->button()->primary('Sende','#')]);
        $button2 = $this->uiFactory->button()->standard('Benachrichtigung senden', '#')
            ->withOnClick($modal2->getShowSignal());
        $this->toolbar->addComponent($button2);


		$list = new WriterAdminLogListGUI($this, "showStartPage", $this->plugin, $this->object->getId());
		$list->addLogEntries([
			(new LogEntry())->setCategory("notice")
				->setEntry("[user=6] hat Personalausweis vorgelegt.")
				->setTitle("Teilnehmer [user=311] ohne Studentenausweis")
				->setTimestamp((new \ilDateTime(time()+60, IL_CAL_UNIX))->get(IL_CAL_DATETIME))
			]);
		$list->addWriterNotices([
			(new WriterNotice())->setWriterId(6)->setTitle("Hinweis zur Angabe")
				->setNoticeText('In Zeile 3 hat sich ein Fehler eingeschlichen. Es muss "Kauf" statt "Verkauf heißen"')
				->setCreated((new \ilDateTime(time(), IL_CAL_UNIX))->get(IL_CAL_DATETIME))
		]);

        $this->tpl->setContent($this->renderer->render([$modal1, $modal2]) . $list->getContent());

     }

	 private function createWriterNotice(){

	 }

	 private function createLogEntry(){

	 }

	private function buildFormModalLogEntry()
	{
		$form = new \ilPropertyFormGUI();
		$form->setId(uniqid('form'));
		$item = new \ilTextInputGUI('Firstname', 'firstname');
		$item->setRequired(true);
		$form->addItem($item);
		$item = new \ilTextAreaInputGUI('Lastname', 'lastname');
		$item->setRequired(true);
		$form->addItem($item);
		$form->addItem(new \ilCountrySelectInputGUI('Country', 'country'));
		$form->setFormAction("#");

		$item = new \ilHiddenInputGUI('cmd');
		$item->setValue('submit');
		$form->addItem($item);

		return $this->buildFormModal($form);
	}


	private function buildFormModal(\ilPropertyFormGUI $form)
	{
		global $DIC;
		$factory = $DIC->ui()->factory();
		$renderer = $DIC->ui()->renderer();

		// Build the form
		$item = new \ilHiddenInputGUI('cmd');
		$item->setValue('submit');
		$form->addItem($item);

		// Build a submit button (action button) for the modal footer
		$form_id = 'form_' . $form->getId();
		$submit = $factory->button()->primary('Submit', '#')
			->withOnLoadCode(function ($id) use ($form_id) {
				return "$('#{$id}').click(function() { $('#{$form_id}').submit(); return false; });";
			});

		// Check if the form was submitted, if validation fails, show it again in a modal
		$out = '';
		$valid = true;
		if (isset($_POST['cmd']) && $_POST['cmd'] == 'submit') {
			if ($form->checkInput()) {
				$panel = $factory->panel()->standard('Form validation successful', $factory->legacy(print_r($_POST, true)));
				$out = $renderer->render($panel);
			} else {
				$form->setValuesByPost();
				$valid = false;
			}
		}

		$modal = $factory->modal()->roundtrip('User Details', $factory->legacy($form->getHTML()))
			->withActionButtons([$submit]);

		// The modal triggers its show signal on load if validation failed
		if (!$valid) {
			$modal = $modal->withOnLoad($modal->getShowSignal());
		}
		$button1 = $factory->button()->standard('Show Form', '#')
			->withOnClick($modal->getShowSignal());

		return $renderer->render([$button1, $modal]) . $out;
	}
}