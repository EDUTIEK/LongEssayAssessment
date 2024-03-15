<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Object\IliasContext;

/**
 * Container for services related to a LongEssayAssessment object
 */
class ObjectServices
{
    protected int $obj_id;
    protected \ILIAS\DI\Container $global_dic;
    protected LongEssayAssessmentDI $local_dic;
    protected \Pimple\Container $object_dic;

    /**
     * @param int $ref_id   ID of the LongEssayAssessment object to which the services relate
     */
    public function __construct(
        int $ref_id,
        \ILIAS\DI\Container $global_dic,
        LongEssayAssessmentDI $local_dic,
        \Pimple\Container $object_dic
    ) {
        $this->obj_id = $ref_id;
        $this->global_dic = $global_dic;
        $this->local_dic = $local_dic;
        $this->object_dic = $object_dic;

        // fill the container
        $object_dic['ilias_context'] = function() use ($ref_id) {
            return new IliasContext($ref_id);
        };
    }

    public function iliasContext() : IliasContext
    {
        return $this->object_dic['ilias_context'];
    }

}