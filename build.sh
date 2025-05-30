CRON_PLUGIN_PATH=$(realpath -m ../../../Cron/CronHook/LongEssayAssessmentCron)
UI_PLUGIN_PATH=$(realpath -m ../../../UIComponent/UserInterfaceHook/LongEssayAssessmentUI)

rm -rf $CRON_PLUGIN_PATH/classes $UI_PLUGIN_PATH/classes
mkdir -p $CRON_PLUGIN_PATH/classes
mkdir -p $UI_PLUGIN_PATH/classes
cp ./plugin.php $CRON_PLUGIN_PATH/plugin.php
cp ./plugin.php $UI_PLUGIN_PATH/plugin.php
sed -i 's/xlas/xlasc/' $CRON_PLUGIN_PATH/plugin.php
sed -i 's/xlas/xlasu/' $UI_PLUGIN_PATH/plugin.php
echo '<?php #dummy from Repository/RepositoryObject/LongEssayAssessment' >  $CRON_PLUGIN_PATH/classes/class.ilLongEssayAssessmentCronPlugin.php
echo '<?php #dummy from Repository/RepositoryObject/LongEssayAssessment' >  $UI_PLUGIN_PATH/classes/class.ilLongEssayAssessmentUIPlugin.php