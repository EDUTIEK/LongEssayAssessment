<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment\Writer;

use ILIAS\Plugin\LongEssayAssessment\Data\Writer\Writer;
use ILIAS\UI\Component\Input\Container\Filter;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\CorrectorAdminService;
use ILIAS\Plugin\LongEssayAssessment\CorrectorAdmin\StatisticsGUI;
use ILIAS\Plugin\LongEssayAssessment\Data\Essay\Essay;
use ILIAS\Plugin\LongEssayAssessment\UI\Component\Statistic;

/**
 *Start page for corrector admins
 *
 * @package ILIAS\Plugin\LongEssayAssessment\Corrector
 * @ilCtrl_isCalledBy ILIAS\Plugin\LongEssayAssessment\Writer\WriterStatisticsGUI: ilObjLongEssayAssessmentGUI, ILIAS\Plugin\LongEssayAssessment\Writer\WriterStartGUI
 */
class WriterStatisticsGUI extends StatisticsGUI
{
    /**
     * Execute a command
     * This should be overridden in the child classes
     * note: permissions are already checked in the object gui
     */
    public function executeCommand()
    {
        $next_class = $this->ctrl->getNextClass();

        switch ($next_class) {

            default:
                $cmd = $this->ctrl->getCmd('showStatistics');
                switch ($cmd) {
                    case 'showStatistics':
                    case 'showStatistics2':
                        $this->$cmd();
                        break;

                    default:
                        $this->tpl->setContent('unknown command: ' . $cmd);
                }
        }
    }

    protected function buildFilter() : Filter\Standard
    {
        $context = [];

        foreach($this->objects as $node) {
            $context[$node["obj_id"]] = $node["title"];
        }

        $base_action = $this->ctrl->getFormAction($this, 'showStatistics');
        return $this->ui_service->filter()->standard("xlas_statistics", $base_action, [
            "context" => $this->uiFactory->input()->field()->multiSelect($this->plugin->txt("objs_xlas"), $context)
                                         ->withValue([$this->object->getId()])
                                         ->withAdditionalOnLoadCode($this->localDI->getUIService()->checkAllInMultiselectFilter())
        ], [true], true, true);
    }

    protected function showStatistics() : void
    {
        $writers = $this->localDI->getWriterRepo()->getWritersByUserId($this->dic->user()->getId());
        $this->loadObjectsInContext();

        foreach($this->objects as $obj) {
            $this->loadDataForObject($obj["obj_id"]);
        }

        $filter_gui = $this->buildFilter();
        $filter_data = $this->ui_service->filter()->getData($filter_gui) ?? ['context' => null];

        $sections = array_filter(
            $this->objects,
            fn ($x) => $filter_data['context'] !== null
                ? in_array($x['obj_id'], $filter_data['context'])
                : (int)$x['obj_id'] === $this->object->getId()
        );
        $this->grade_level = array_intersect_key($this->grade_level, array_flip($filter_data['context'] ?? [$this->object->getId()]));

        $writers = array_filter($writers,
            fn (Writer $x) => $filter_data['context'] !== null
                ? in_array($x->getTaskId(), $filter_data['context'])
                : (int)$x->getTaskId() === $this->object->getId()
        );

        $writer_ids = [];
        foreach($writers as $writer){
            $writer_ids[$writer->getTaskId()] = $writer->getId();
        }

        if(count($sections) > 1){
            $overall_statistic = $this->service->gradeStatistics(array_merge(...$this->essays));
            $overall_own_statistic = $this->service->gradeStatistics(array_filter(array_merge(...$this->essays),fn (Essay $x) => in_array($x->getWriterId(), $writer_ids)));
            $overall_grade_distribution = $this->getGradeStatisticOverAll($overall_statistic);
            $overall_own_grade_distribution = $this->getGradeStatisticOverAll($overall_own_statistic);

            $items = [
                $this->createStatisticItem($this->plugin->txt("total_statistic"), $overall_statistic)->withGrades($overall_grade_distribution),
                $this->createStatisticItem($this->plugin->txt("own_assessments"), $overall_own_statistic)->withGrades($overall_own_grade_distribution),
                $this->localDI->getUIFactory()->statistic()->statisticSection("Klausuren")
            ];

            foreach($sections as $section) {
                $items[] = $this->fillSection($section, $writer_ids);
            }

            $root = $this->localDI->getUIFactory()->statistic()->extendableStatisticGroup($this->plugin->txt("statistic"), $items);
        }else{
            $section = array_pop($sections);
            $root = $this->fillSection($section, $writer_ids)->withTitle($this->plugin->txt("statistic"));
        }

        $this->tpl->setContent($this->renderer->render([$filter_gui, $root]));
    }

    private function fillSection($section, $writer_ids): Statistic
    {
        $obj_id = $section["obj_id"];
        $corrector_service = $this->localDI->getCorrectorAdminService($obj_id);
        $own_essays = array_filter($this->essays[$obj_id],fn (Essay $x) => $x->getWriterId() === $writer_ids[$this->object->getId()] ?? -1);
        $own_grade = ($essay = array_pop($own_essays)) !== null ? $this->data->formatFinalResult($essay) : null;
        $section_statistic = $corrector_service->gradeStatistics($this->essays[$obj_id]);;
        $section_grade_distribution = $this->getGradeStatisticOverAll($section_statistic);
        $item = $this->createStatisticItem($section['title'], $section_statistic)
                     ->withGrades($section_grade_distribution);
        if($own_grade !== null){
            $item = $item->withOwnGrade($own_grade);
        }
        return $item;
    }

    protected function loadObjectsInContext(): void
    {
        $objects = $this->object_services->iliasContext()->getAllEssaysInThisContext();

        $this->objects = array_filter($objects, function ($object){
            $obj = new \ilObjLongEssayAssessment($object['ref_id']);
            return $obj->canViewWriterStatistics();
        });

    }

}
