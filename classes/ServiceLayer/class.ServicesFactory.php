<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ILIAS\DI\Container;
use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;

class ServicesFactory
{
    protected Container $global_dic;
    protected LongEssayAssessmentDI $local_dic;

    protected array $objectServices = [];
    protected array $taskServices = [];

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
     * Get the services container for an object
     */
    function objectServices(int $ref_id) : ObjectServices
    {
        if (!isset($this->objectServices[$ref_id])) {
            $this->objectServices[$ref_id] = new ObjectServices(
                $ref_id,
                $this->global_dic,
                $this->local_dic,
                new \Pimple\Container()
            );
        }
        return $this->objectServices[$ref_id];
    }
}