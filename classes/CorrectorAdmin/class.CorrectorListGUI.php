<?php

namespace ILIAS\Plugin\LongEssayAssessment\WriterAdmin;

use Exception;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector;
use ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment;

class CorrectorListGUI extends WriterListGUI
{

    /**
     * @var \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[]
     */
    private $correctors = [];

    /**
     * @var CorrectorAssignment[][]
     */
    private $assignments = [];

    public function getContent():string
    {
        $this->loadUserData();
        $this->sortCorrector();
        $this->sortAssignments();

        $modals = [];
        $items = [];

        foreach ($this->correctors as $corrector) {
            $actions = [];

            if($this->canGetRemoved($corrector)) {
                $remove_modal = $this->uiFactory->modal()->interruptive(
                    $this->plugin->txt("remove_corrector"),
                    $this->plugin->txt("remove_corrector_confirmation"),
                    $this->getRemoveCorrectorAction($corrector)
                )->withAffectedItems([
                    $this->uiFactory->modal()->interruptiveItem(
                        $corrector->getUserId(),
                        $this->getUsernameText($corrector->getUserId()),
                        $this->getUserImage($corrector->getUserId())
                    )
                ])->withActionButtonLabel("remove");

                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("remove_corrector"), '')
                    ->withOnClick($remove_modal->getShowSignal());

                $this->ctrl->setParameter($this->parent, 'corrector_id', $corrector->getId());
                $actions[] = $this->uiFactory->button()->shy($this->plugin->txt("write_mail"),
                    $this->ctrl->getLinkTarget($this->parent, 'mailToSingleCorrector')
                );

                $modals[] = $remove_modal;
            }

            $writers = [];
            foreach ($this->getAssignmentsByCorrector($corrector) as $assignment) {
                $pos = $this->localDI->getDataService($corrector->getTaskId())->formatCorrectorPosition($assignment);
                $writers["&nbsp;&nbsp;" . $this->getWriterNameText($this->writers[$assignment->getWriterId()])] = $pos;
            }

            //if(($icon = $this->getUserIcon($corrector->getUserId())) !== null) {
            //  $item->withLeadIcon($icon);
            //}


            $assigned = [];

            if(count($writers) > 0) {
                $assigned = $this->uiFactory->listing()->characteristicValue()->text($writers);
            }
            $item = $this->uiFactory->panel()->sub($this->getUsernameText($corrector->getUserId()), $assigned);

            if(count($actions) > 0) {
                $actions_dropdown = $this->uiFactory->dropdown()->standard($actions)
                    ->withLabel($this->plugin->txt("actions"));
                $item =  $item->withActions($actions_dropdown);
            }

            $items[] = $item;
        }

        return $this->renderer->render(array_merge([$this->uiFactory->panel()->standard(
            $this->plugin->txt("correctors") . $this->localDI->getDataService(0)->formatCounterSuffix(count($this->correctors)),
            $items
        )], $modals));
    }

    private function canGetRemoved(Corrector $corrector):bool
    {
        return ! array_key_exists($corrector->getId(), $this->assignments);
    }

    private function getRemoveCorrectorAction(Corrector $corrector): string
    {
        $this->ctrl->setParameter($this->parent, "corrector_id", $corrector->getId());
        return $this->ctrl->getLinkTarget($this->parent, "removeCorrector");
    }

    /**
     * @return \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\Corrector[]
     */
    public function getCorrectors(): array
    {
        return $this->correctors;
    }

    /**
     * @param Corrector[] $correctors
     */
    public function setCorrectors(array $correctors): void
    {
        $this->correctors = $correctors;

        foreach ($correctors as $corrector) {
            $this->user_ids[] = $corrector->getUserId();
        }
    }

    /**
     * @return CorrectorAssignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    /**
     * @param \ILIAS\Plugin\LongEssayAssessment\Data\Corrector\CorrectorAssignment[] $assignments
     */
    public function setAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            if (! isset($this->assignments[$assignment->getCorrectorId()])) {
                $this->assignments[$assignment->getCorrectorId()] = [];
            }
            $this->assignments[$assignment->getCorrectorId()][] = $assignment;
        }
    }

    /**
     * @param Corrector $corrector
     * @return array|CorrectorAssignment[]
     */
    private function getAssignmentsByCorrector(Corrector $corrector): array
    {
        if (array_key_exists($corrector->getId(), $this->assignments)) {
            return $this->assignments[$corrector->getId()];
        }
        return [];
    }

    /**
     * @param callable|null $custom_sort Custom sortation callable. Equal writer will be sorted by name.
     * @return void
     */
    protected function sortCorrector(callable $custom_sort = null)
    {
        $this->sortWriterOrCorrector($this->correctors, $custom_sort);
    }

    /**
     * Sort Assignments primarily by position (lowest position comes first), secondary by name
     * @return void
     * @throws Exception
     */
    protected function sortAssignments()
    {
        if(!$this->user_data_loaded) {
            throw new Exception("sortAssignments was called without loading usernames.");
        }

        $names = [];
        foreach ($this->user_data as $usr_id => $name) {
            $names[$usr_id] = strip_tags($name);
        }

        $by_name = function (CorrectorAssignment $a, CorrectorAssignment$b) use ($names) {
            $rating = $a->getPosition() - $b->getPosition();

            if($rating !== 0) {
                return $rating;
            }

            if(!array_key_exists($a->getWriterId(), $this->writers)) {
                return -1;
            }
            if (!array_key_exists($b->getWriterId(), $this->writers)) {
                return 1;
            }

            $writer_a = $this->writers[$a->getWriterId()];
            $writer_b = $this->writers[$b->getWriterId()];

            $name_a = array_key_exists($writer_a->getUserId(), $names) ? $names[$writer_a->getUserId()] : "ÿ";
            $name_b = array_key_exists($writer_b->getUserId(), $names) ? $names[$writer_b->getUserId()] : "ÿ";

            return strcasecmp($name_a, $name_b);
        };

        foreach ($this->assignments as $corrector_id => $assignment) {
            usort($this->assignments[$corrector_id], $by_name);
        }
    }
}
