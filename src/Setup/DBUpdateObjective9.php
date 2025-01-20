<?php

namespace ILIAS\Plugin\LongEssayAssessment\Setup;

use ILIAS\Setup\Environment;
use ilDatabaseUpdateSteps;

class DBUpdateObjective9 extends \ilDatabaseUpdateStepsExecutedObjective
{
    public function __construct()
    {
        parent::__construct(new DBUpdateSteps9());
    }

    /**
     * Only Applicable if plugin was already installed, otherwise don't bother with the old structure
     *
     * @param Environment $environment
     * @return bool
     */
    public function isApplicable(Environment $environment): bool
    {
        /**
         * @var \ilDBInterface $db
         */
        $db = $environment->getResource(Environment::RESOURCE_DATABASE);
        $query = $db->query("SELECT * FROM il_plugin WHERE plugin_id = 'xlas'");

        if ($ret = $db->fetchAssoc($query)) {
            return ((int)$ret['db_version']) > 0 && parent::isApplicable($environment);
        }
        return false;
    }

}