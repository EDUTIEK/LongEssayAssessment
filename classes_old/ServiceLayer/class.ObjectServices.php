<?php

namespace ILIAS\Plugin\LongEssayAssessment\ServiceLayer;

use ILIAS\Plugin\LongEssayAssessment\LongEssayAssessmentDI;
use ILIAS\Plugin\LongEssayAssessment\ServiceLayer\Object\IliasContext;

/**
 * Container for services related to a LongEssayAssessment object
 */
class ObjectServices
{
    protected \ILIAS\DI\Container $global_dic;
    protected LongEssayAssessmentDI $local_dic;
    protected \Pimple\Container $service_dic;

    /**
     * @param int $ref_id   ref_id of the LongEssayAssessment object to which the services relates
     */
    public function __construct(
        int $ref_id,
        \ILIAS\DI\Container $global_dic,
        LongEssayAssessmentDI $local_dic,
        \Pimple\Container $service_dic
    ) {
        $this->global_dic = $global_dic;
        $this->local_dic = $local_dic;
        $this->service_dic = $service_dic;

        // fill the container
        $service_dic['ilias_context'] = function() use ($ref_id) {
            return new IliasContext($ref_id);
        };
    }

    public function iliasContext() : IliasContext
    {
        return $this->service_dic['ilias_context'];
    }

}