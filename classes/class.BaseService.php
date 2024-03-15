<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayAssessment;

/**
 * Base class for service classes
 * @author Fred Neumann <fred.neumann@ilias.de>
 */
abstract class BaseService
{
    public \ILIAS\DI\Container $dic;
    public \ilLanguage $lng;
    public \ilLongEssayAssessmentPlugin $plugin;
    protected LongEssayAssessmentDI $localDI;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $DIC;

        // ILIAS dependencies
        $this->dic = $DIC;
        $this->lng = $this->dic->language();

        // Plugin dependencies
        $this->plugin = \ilLongEssayAssessmentPlugin::getInstance();
        $this->localDI = LongEssayAssessmentDI::getInstance();
    }
}
