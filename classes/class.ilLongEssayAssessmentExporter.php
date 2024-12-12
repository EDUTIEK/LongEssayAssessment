<?php

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\Data\Object\ObjectRepository;
use ILIAS\Plugin\LongEssayAssessment\Data\Task\TaskRepository;

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

class ilLongEssayAssessmentExporter extends ilXmlExporter
{
    private LongEssayAssessmentDI $localDI;
    private ilLongEssayAssessmentPlugin $plugin;

    private ObjectRepository $object_repo;
    private TaskRepository $task_repo;

    private ilObjLongEssayAssessment $object;

    public function __construct()
    {
        $this->localDI = LongEssayAssessmentDI::getInstance();
        $this->plugin = ilLongEssayAssessmentPlugin::getInstance();

        $object_repo = $this->localDI->getObjectRepo();
        $task_repo = $this->localDI->getTaskRepo();
    }

    public function getXmlExportTailDependencies(
        string $a_entity,
        string $a_target_release,
        array $a_ids
    ): array {
        $deps = [];
        // service settings
        $deps[] = [
            "component" => "Services/Object",
            "entity" => "common",
            "ids" => $a_ids
        ];

        return $deps;
    }

    public function getXmlRepresentation(string $a_entity, string $a_schema_version, string $a_id) : string
    {
        if (ilObject::_lookupType((int) $a_id) !== 'xlas') {
            return '';
        }

        $ref_ids = ilObject::_getAllReferences($a_id);
        $ref_id = array_shift($ref_ids);
        $this->object = new ilObjLongEssayAssessment($ref_id);

        $writer = new ilXmlWriter();
        $writer->xmlStartTag("xlas");

        // basic object data
        $writer->xmlElement("title", null, $this->object->getTitle());
        $writer->xmlElement("description", null, $this->object->getDescription());

        // LOM meta data
        $md2xml = new ilMD2XML($this->object->getId(), $this->object->getId(), 'xlas');
        $md2xml->startExport();
        $writer->appendXML($md2xml->getXML());

        $writer->xmlEndTag("xlas");

        return $writer->xmlDumpMem(false);
    }

    public function init() : void
    {
        // TODO: Implement init() method.
    }

    public function getValidSchemaVersions(string $a_entity) : array
    {
        return array(
            "5.2.0" => array(
                "namespace" => "http://www.ilias.de/Plugins/LongEssayAssessment/md/5_2",
                "xsd_file" => "ilias_md_5_2.xsd",
                "min" => "5.2.0",
                "max" => ""
            )
        );
    }
}