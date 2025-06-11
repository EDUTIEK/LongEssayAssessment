#!/bin/bash

CRON_PLUGIN_PATH=$(realpath -m ../../../Cron/CronHook/LongEssayAssessmentCron)
UI_PLUGIN_PATH=$(realpath -m ../../../UIComponent/UserInterfaceHook/LongEssayAssessmentCollection)
echo " Creating new folders and files for optional plugins..."

echo -e " \033[32mInfo: This script operates outside of this directory.\033[0m";

rm -rf $CRON_PLUGIN_PATH/classes $UI_PLUGIN_PATH/classes
mkdir -p $CRON_PLUGIN_PATH/classes
mkdir -p $UI_PLUGIN_PATH/classes
cp ./plugin.php $CRON_PLUGIN_PATH/plugin.php
cp ./plugin.php $UI_PLUGIN_PATH/plugin.php
sed -i -e 's/xlas/xlasc/' -e '/\/\/ features/,$d' $CRON_PLUGIN_PATH/plugin.php
sed -i -e 's/xlas/xlasu/' -e '/\/\/ features/,$d' $UI_PLUGIN_PATH/plugin.php
echo '<?php #dummy from Repository/RepositoryObject/LongEssayAssessment' >  $CRON_PLUGIN_PATH/classes/class.ilLongEssayAssessmentCronPlugin.php
echo '<?php #dummy from Repository/RepositoryObject/LongEssayAssessment' >  $UI_PLUGIN_PATH/classes/class.ilLongEssayAssessmentCollectionPlugin.php

echo " Created CronHook plugin at $CRON_PLUGIN_PATH"
echo " Created UserInterfaceHook plugin at $UI_PLUGIN_PATH"

echo " Done."
