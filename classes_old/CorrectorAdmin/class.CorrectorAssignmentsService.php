<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin;

use ILIAS\Plugin\LongEssayAssessment\BaseService;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\CorrectionSettings;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\EssayRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\WriterRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\Plugin\LongEssayAssessment\WriterAdmin\WriterAdminService;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ilFileDelivery;

class CorrectorAssignmentsService extends BaseService
{
    protected CorrectionSettings $settings;
    protected WriterRepository $writer_repo;
    protected CorrectorRepository $corrector_repo;
    protected EssayRepository $essay_repo;
    protected TaskRepository $task_repo;
    protected WriterAdminService $writer_admin_service;
    protected CorrectorAdminService $corrector_admin_service;

    protected int $task_id;

    /**
     * Constructor
     */
    public function __construct(int $task_id)
    {
        parent::__construct();
        $this->task_id = $task_id;

        $this->writer_repo = $this->localDI->getWriterRepo();
        $this->corrector_repo = $this->localDI->getCorrectorRepo();
        $this->essay_repo = $this->localDI->getEssayRepo();
        $this->task_repo = $this->localDI->getTaskRepo();
        $this->writer_admin_service = $this->localDI->getWriterAdminService($this->task_id);
        $this->corrector_admin_service = $this->localDI->getCorrectorAdminService($this->task_id);
        $this->settings = $this->task_repo->getCorrectionSettingsById($this->task_id);
    }

    /**
     * Export assignment to an excel file
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportAssignments(bool $only_authorized): void
    {
        $participant_title = "Writer";
        $corrector_title = "Corrector";
        $locations = [];

        foreach($this->task_repo->getLocationsByTaskId($this->task_id) as $location) {
            $locations[$location->getId()] = $location;
        }

        $essays = [];

        foreach($this->essay_repo->getEssaysByTaskId($this->task_id) as $essay) {
            $essays[$essay->getWriterId()] = $essay;
        }
        $corrector = [];
        foreach($this->corrector_repo->getCorrectorsByTaskId($this->task_id) as $r) {
            $corrector[$r->getId()] = $r;
        }

        $writer = $this->writer_repo->getWritersByTaskId($this->task_id);

        if ($only_authorized) {
            $authorized_user = [];
            foreach($essays as $writer_id => $essay) {
                if($essay->getWritingAuthorized() !== null) {
                    $authorized_user[] = $essay->getWriterId();
                }
            }
            $writer = array_filter($writer, fn ($x) => in_array($x->getId(), $authorized_user));
        }

        $users = [];

        foreach(\ilObjUser::_getUserData(array_merge(
            array_map(fn (Corrector $x) => $x->getUserId(), $corrector),
            array_map(fn (Writer $x) => $x->getUserId(), $writer),
        )) as $u) {
            $users[(int)$u['usr_id']] = $u;
        }

        $assignments = [];

        foreach($this->corrector_repo->getAssignmentsByTaskId($this->task_id) as $assignment) {
            $assignments[$assignment->getWriterId()][$assignment->getPosition()] = $assignment;
        }
        $r = 2;
        $spreadsheet = new CorrectorAssignmentExcel();
        $writer_sheet = $spreadsheet->addSheet($participant_title, true);
        $corrector_sheet = $spreadsheet->addSheet($corrector_title, false);
        $spreadsheet->setCell(1, 0, 'Login');
        $spreadsheet->setCell(1, 1, 'Firstname');
        $spreadsheet->setCell(1, 2, 'Lastname');
        $spreadsheet->setCell(1, 3, 'Email');
        $spreadsheet->setCell(1, 4, 'Pseudonym');
        $spreadsheet->setCell(1, 5, 'Location');
        $spreadsheet->setCell(1, 6, 'Words');
        foreach(range(0, $this->settings->getRequiredCorrectors() -1 ) as $pos) {
            $spreadsheet->setCell(1, 7 + $pos, 'Corrector ' . ($pos + 1));
        }

        foreach($writer as $w) {
            $data = $users[$w->getUserId()] ?? [];
            $ass = $assignments[$w->getId()] ?? [];
            ksort($ass);
            $essay = $essays[$w->getId()] ?? null;
            $location = $essay !== null ? $locations[$essay->getLocation()] ?? null : null;
            $written_text = $essay !== null ? $essay->getWrittenText() : "";
            $location_text = $location !== null ? $location->getTitle() : "";

            $spreadsheet->setCell($r, 0, $data['login'] ?? "");
            $spreadsheet->setCell($r, 1, $data['firstname'] ?? "");
            $spreadsheet->setCell($r, 2, $data['lastname'] ?? "");
            $spreadsheet->setCell($r, 3, $data['email'] ?? "");
            $spreadsheet->setCell($r, 4, $w->getPseudonym());
            $spreadsheet->setCell($r, 5, $location_text);
            $spreadsheet->setCell($r, 6, str_word_count($written_text ?? ""));

            foreach(range(0, $this->settings->getRequiredCorrectors()-1) as $pos) {
                $spreadsheet->addDropdownCol($r, 7 + $pos, '=\''.$corrector_title.'\'!$A$2:$A$'.(count($corrector)+1));
            }

            foreach ($ass as $a) {
                $c = $corrector[$a->getCorrectorId()] ?? null;
                $login = $c !== null && isset($users[$c->getUserId()]) ? $users[$c->getUserId()]['login'] ?? '' : '';
                $spreadsheet->setCell($r, 7+$a->getPosition(), $login);
            }
            $r++;
        }
        $r = 2;
        $spreadsheet->setActiveSheet($corrector_sheet);
        $spreadsheet->setCell(1, 0, 'Login');
        $spreadsheet->setCell(1, 1, 'Firstname');
        $spreadsheet->setCell(1, 2, 'Lastname');
        $spreadsheet->setCell(1, 3, 'Email');

        foreach($corrector as $c) {
            $data = $users[$c->getUserId()] ?? [];
            $ass = $assignments[$c->getId()] ?? [];
            ksort($ass);

            $spreadsheet->setCell($r, 0, $data['login'] ?? "");
            $spreadsheet->setCell($r, 1, $data['firstname'] ?? "");
            $spreadsheet->setCell($r, 2, $data['lastname'] ?? "");
            $spreadsheet->setCell($r, 3, $data['email'] ?? "");
            $r++;
        }
        $spreadsheet->setActiveSheet($corrector_sheet);

        $file = $spreadsheet->writeToTmpFile();
        ilFileDelivery::deliverFileAttached($file, "corrector_assignment.xlsx", "", false, false);
    }

    /**
     * Import assignments from an exel file
     * Writers and correctors will be created by login on the fly
     * @throws CorrectorAssignmentsException
     */
    public function importAssignments(string $file)
    {
        $excel = new CorrectorAssignmentExcel();
        $excel->loadFromFile($file);
        $excel->setActiveSheet(0);
        $errors = [];

        // check if the required column labels are present
        $columns = $excel->getColumnTitlesFromActiveSheet();
        $required_labels = ['Login'];
        for ($pos = 0; $pos < $this->settings->getRequiredCorrectors(); $pos++)  {
            $required_labels[] = 'Corrector ' . ($pos + 1);
        }
        foreach ($required_labels as $label) {
            if (!in_array($label, $columns)) {
                $errors[] = sprintf($this->plugin->txt('import_missing_column'), $label);
            }
        }

        if (!empty($errors)) {
            throw new CorrectorAssignmentsException(implode("\n", $errors));
        }

        // collect writers, correctors and assignments (saved later)
        $writers = [];      // login => Writer
        $correctors = [];   // login => Corrector
        $to_assign = [];    // login => position (0-x) => login

        foreach ($excel->getAssocDataFromActiveSheet() as $line => $record) {

            if (!empty($writer_login = $record['Login'] ?? '')) {
                $to_assign[$writer_login] = [];

                if (empty($writer_user_id = \ilObjUser::_lookupId($writer_login))) {
                    $errors[] = sprintf($this->plugin->txt('import_line_user_not_found'), $line + 1, $writer_login);
                    continue; // next writer
                }
                elseif (!empty($writers[$writer_login])) {
                    $errors[] = sprintf($this->plugin->txt('import_line_writer_repeated'), $line + 1, $writer_login);
                    continue; // next writer
                }
                $writers[$writer_login] = $this->writer_admin_service->getWriterFromUserId($writer_user_id);

                for ($pos = 0; $pos < $this->settings->getRequiredCorrectors(); $pos++) {

                    if (!empty($corrector_login = $record['Corrector ' . ($pos + 1)] ?? '')) {

                        if (in_array($corrector_login, $to_assign[$writer_login])) {
                            $errors[] = sprintf($this->plugin->txt('import_line_corrector_repeated'), $line + 1, $corrector_login);
                            continue; // next corrector
                        }
                        elseif (empty($corrector_user_id = \ilObjUser::_lookupId($corrector_login))) {
                            $errors[] = sprintf($this->plugin->txt('import_line_user_not_found'),$line + 1, $corrector_login);
                            continue; // next corrector
                        }

                        if (empty($correctors[$corrector_login])) {
                            $correctors[$corrector_login] = $this->corrector_admin_service->getCorrectorFromUserId($corrector_user_id);
                        }

                        $to_assign[$writer_login][$pos] = $corrector_login;
                    }
                }
            }
        }

        if (!empty($errors)) {
            throw new CorrectorAssignmentsException(implode("\n", $errors));
        }


        /** @var Writer[] $writers */
        foreach ($writers as $writer) {
            if (empty($writer->getId())) {
                $this->writer_admin_service->saveNewWriter($writer);
            }
        }
        /** @var Corrector[] $correctors */
        foreach ($correctors as $corrector) {
            if (empty($corrector->getId()))
            $this->corrector_repo->save($corrector);
        }

        foreach ($to_assign as $writer_login => $corrector_logins) {
            $writer_id = $writers[$writer_login]->getId();

            $corrector1_id = empty($corrector_logins[0])
                ? CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT
                : $correctors[$corrector_logins[0]]->getId();

            $corrector2_id = empty($corrector_logins[1])
                ? CorrectorAdminService::BLANK_CORRECTOR_ASSIGNMENT
                : $correctors[$corrector_logins[1]]->getId();

            $this->corrector_admin_service->assignMultipleCorrector(
                $corrector1_id,
                $corrector2_id,
                [$writer_id]
            );
        }
    }
}