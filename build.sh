#!/bin/bash

CRON_PLUGIN_PATH=$(realpath -m ../../../Cron/CronHook/LongEssayAssessmentCron)
UI_PLUGIN_PATH=$(realpath -m ../../../UIComponent/UserInterfaceHook/LongEssayAssessmentCollection)
echo " Creating new folders and files for optional plugins..."

echo -e " \033[32mInfo: This script operates outside of this directory.\033[0m";

rm -rf $CRON_PLUGIN_PATH/ $UI_PLUGIN_PATH/classes
mkdir -p $UI_PLUGIN_PATH/classes
cp ./plugin.php $UI_PLUGIN_PATH/plugin.php
sed -i -e 's/xlas/xlasu/' -e '/\/\/ features/,$d' $UI_PLUGIN_PATH/plugin.php
echo '<?php #dummy from Repository/RepositoryObject/LongEssayAssessment' >  $UI_PLUGIN_PATH/classes/class.ilLongEssayAssessmentCollectionPlugin.php

echo " Created UserInterfaceHook plugin at $UI_PLUGIN_PATH"

echo " Done."
