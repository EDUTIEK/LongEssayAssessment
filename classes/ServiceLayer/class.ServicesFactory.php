<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;

class ServicesFactory
{
    protected Container $global_dic;
    protected LongEssayAssessmentDI $local_dic;

    protected array $common_services = [];
    protected array $object_services = [];
    protected array $task_services = [];

    /**
     * Constructor
     */
    public function __construct(
        Container $global_dic,
        LongEssayAssessmentDI $local_dic
    ) {
        $this->global_dic = $global_dic;
        $this->local_dic = $local_dic;
    }

    /**
     * Get the common services container (currently without parameter)
     */
    function common() : CommonServices
    {
        if (!isset($this->common_services[0])) {
            $this->common_services[0] = new CommonServices(
                $this->global_dic,
                $this->local_dic,
                new \Pimple\Container()
            );
        }
        return $this->common_services[0];
    }

    /**
     * Get the services container for an object
     */
    function object(int $ref_id) : ObjectServices
    {
        if (!isset($this->object_services[$ref_id])) {
            $this->object_services[$ref_id] = new ObjectServices(
                $ref_id,
                $this->global_dic,
                $this->local_dic,
                new \Pimple\Container()
            );
        }
        return $this->object_services[$ref_id];
    }

}