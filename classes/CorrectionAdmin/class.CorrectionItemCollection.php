<?php

namespace ILIAS\Plugin\LongEssayAssessment\CorrectionAdmin;

use Edutiek\AssessmentService\Assessment\Data\Writer;
use Edutiek\AssessmentService\Assessment\Data\Corrector;
use Edutiek\AssessmentService\EssayTask\Data\Essay;
use Edutiek\AssessmentService\Task\AssessmentStatus\CombinedStatus;
use Edutiek\AssessmentService\Task\Data\CorrectorAssignment;
use Edutiek\AssessmentService\Task\Data\CorrectorSummary;
use Edutiek\AssessmentService\System\Data\UserDisplay;
use ILIAS\Plugin\LongEssayAssessment\Assessment\Data\Location;

final class CorrectionItemCollection implements \Iterator
{
    private int $position = 0;
    private array $ids = [];
    private array $writer = [];
    private array $correctors = [];
    private array $essays = [];
    private array $corrector_assignments = [];
    private array $summaries = [];
    private array $user_ids = [];
    private array $user_data;
    private array $user_displays = [];
    private array $locations = [];

    /**
     * @param Writer[] $writer
     * @param UserDisplay[] $user_displays
     * @param Corrector[] $correctors
     * @param Essay[] $essays
     * @param CombinedStatus[] $correction_status
     * @param CorrectorAssignment[] $corrector_assignments
     * @param CorrectorSummary[] $summaries
     * @param Location[] $locations
     */
    public function __construct(
        array $writer,
        array $user_displays,
        array $correctors,
        array $essays,
        private array $correction_status,
        array $corrector_assignments,
        array $summaries,
        array $locations,
        private int $correctors_needed
    ) {
        $correctors_by_id = [];
        $assignment_by_writer_corrector = [];
        $user_ids = [];
        $locations_by_id = [];

        foreach($locations as $l) {
            $locations_by_id[$l->getId()] = $l;
        }

        foreach ($writer as $w) {
            $this->writer[$w->getId()] = $w;
            $user_ids[] = $w->getUserId();
            $this->summaries[$w->getId()] = [];
            $this->correctors[$w->getId()] = [];
            $this->corrector_assignments[$w->getId()] = [];
            $this->locations[$w->getId()] = $locations_by_id[$w->getLocation()]?? null;

            if ($w->getWritingAuthorizedBy() !== null) {
                $user_ids[] = $w->getWritingAuthorizedBy();
            }
            if ($w->getWritingExcludedBy() !== null) {
                $user_ids[] = $w->getWritingExcludedBy();
            }
            if ($w->getCorrectionFinalized() !== null) {
                $user_ids[] = $w->getCorrectionFinalized();
            }
        }

        foreach ($user_displays as $ud) {
            $this->user_displays[$ud->getId()] = $ud;
        }

        foreach ($essays as $e) {
            $this->essays[$e->getWriterId()] = $e;
        }

        foreach ($correctors as $c) {
            $correctors_by_id[$c->getId()] = $c;
            $user_ids[] = $c->getUserId();
        }

        foreach ($corrector_assignments as $ca) {
            $corrector = $correctors_by_id[$ca->getCorrectorId()] ?? null;
            $this->corrector_assignments[$ca->getWriterId()][$ca->getPosition()] = $ca;
            $assignment_by_writer_corrector[$ca->getWriterId()][$ca->getCorrectorId()] = $ca;
            if($corrector !== null){
                $this->correctors[$ca->getWriterId()][$ca->getPosition()] = $corrector;
            }
        }

        foreach ($summaries as $s) {
            $essay = $this->essays[$s->getWriterId()]??null;
            if($essay !== null) {
                $assignment = $assignment_by_writer_corrector[$essay->getWriterId()][$s->getCorrectorId()] ?? null;
                $this->summaries[$essay->getWriterId()][$assignment?->getPosition()] = $s;
            }
        }
        $this->user_ids = array_unique($user_ids);
        $this->ids= array_keys($this->writer);
    }

    public function getUserIds()
    {
        return $this->user_ids;
    }

    public function setUserData(array $user_data)
    {
        $this->user_data = $user_data;
    }

    public function applyFilter(?array $filter_data)
    {
        if(empty($filter)) {
            $this->ids = array_keys($this->writer);
            return;
        }

        $this->ids = [];
        foreach($this->writer as $id => $w)
        {
            $user_data = $this->user_data[$w->getUserId()] ?? null;
            $essay = $this->essays[$id] ?? null;
            if(!empty($filter_data["name"]??null) && !str_contains($w->getPseudonym() . $user_data?->getFullname(true), $filter_data['name'])) {
                continue;
            }
            if (!empty($filter_data['location']?? null) && !in_array($w->getLocation(), $filter_data['location'])) {
                continue;
            }
            if (!empty($filter_data['min_words']??null) && ($essay?->getWords()??0) < (int)$filter_data['min_words']) {
                continue;
            }

            if (!empty($filter_data['max_words']??null) && ($essay?->getWords()??0) > (int)$filter_data['max_words']) {
                continue;
            }

            if (!empty($filter_data['pdf_version']??null)) {
                $has_pdf_upload = $essay?->hasPdfUploads() ?? false;
                $filter_pdf_upload = $filter_data['pdf_version'] === CorrectionAdminGUI::FILTER_YES;

                if ($has_pdf_upload != $filter_pdf_upload) {
                    continue;
                }
            }
            $status = $this->correction_status[$id] ?? null;

            if (!empty($filter_data['status']??null) && !in_array($status->value, $filter_data['status'])) {
                continue;
            }

            if(!empty($filter_data['assigned']??null)){
                $need_correctors = count($this->corrector_assignments[$id]) === $this->correctors_needed;

                if ($filter_data['assigned'] == CorrectionAdminGUI::FILTER_YES && !$need_correctors)  {
                    continue;
                }
                if ($filter_data['assigned'] == CorrectionAdminGUI::FILTER_NO && $need_correctors)  {
                    continue;
                }
            }


            $this->ids[] = $id;
        }
    }

    public function current(): CorrectionItem
    {
        $id = $this->ids[$this->position];

        return new CorrectionItem(
            $id,
            $this->writer[$id],
            $this->user_data,
            $this->locations[$id],
            $this->essays[$id] ?? null,
            $this->correction_status[$id] ?? null,
            $this->summaries[$id],
            $this->correctors[$id],
            $this->user_displays[$id] ?? null
        );
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return isset($this->ids[$this->position]);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

}
