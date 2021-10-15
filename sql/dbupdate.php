<#1>
<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * ExamOrga plugin: database update script
 *
 * @author Fred Neumann <fred.neumann@ilias.de>
 */

/** @var ilDBInterface $ilDB */
?>
<#2>
<?php
require_once('Services/Migration/DBUpdate_3560/classes/class.ilDBUpdateNewObjectType.php');
$type_id = ilDBUpdateNewObjectType::addNewType('xlet', 'Long Essay Task');
$ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('maintain_task', 'Maintain Task Definition', 'object', 3200);
ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
$ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('maintain_writers', 'Maintain Writers', 'object', 3210);
ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
$ops_id = ilDBUpdateNewObjectType::addCustomRBACOperation('maintain_correctors', 'Maintain Correctors', 'object', 3220);
ilDBUpdateNewObjectType::addRBACOperation($type_id, $ops_id);
?>

<#3>
<?php
$fields = array(
    'id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'eskript_url' => array(
        'type' => 'text',
        'length' => '250',

    ),
    'eskript_key' => array(
        'type' => 'text',
        'length' => '250',

    ),
    'writer_url' => array(
        'type' => 'text',
        'length' => '250',

    ),
    'corrector_url' => array(
        'type' => 'text',
        'length' => '250',

    ),

);
if (! $ilDB->tableExists('xlet_plugin_config')) {
    $ilDB->createTable('xlet_plugin_config', $fields);
    $ilDB->addPrimaryKey('xlet_plugin_config', array( 'id' ));
}
?>
<#4>
<?php
$fields = array(
    'obj_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'online' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'participation_type' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',

    ),

);
if (! $ilDB->tableExists('xlet_object_settings')) {
    $ilDB->createTable('xlet_object_settings', $fields);
    $ilDB->addPrimaryKey('xlet_object_settings', array( 'obj_id' ));}
?>
<#5>
<?php
$fields = array(
    'task_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'description' => array(
        'type' => 'clob',

    ),
    'instructions' => array(
        'type' => 'clob',

    ),
    'writing_start' => array(
        'type' => 'timestamp',

    ),
    'writing_end' => array(
        'type' => 'timestamp',

    ),
    'correction_start' => array(
        'type' => 'timestamp',

    ),
    'correction_end' => array(
        'type' => 'timestamp',

    ),
    'review_start' => array(
        'type' => 'timestamp',

    ),
    'review_end' => array(
        'type' => 'timestamp',

    ),

);
if (! $ilDB->tableExists('xlet_task_settings')) {
    $ilDB->createTable('xlet_task_settings', $fields);
    $ilDB->addPrimaryKey('xlet_task_settings', array( 'task_id' ));
}
?>
<#6>
<?php
$fields = array(
    'task_id' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'headline_scheme' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',

    ),
    'formatting_options' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '250',

    ),
    'notice_boards' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'copy_allowed' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),

);
if (! $ilDB->tableExists('xlet_editor_settings')) {
    $ilDB->createTable('xlet_editor_settings', $fields);
    $ilDB->addPrimaryKey('xlet_editor_settings', array( 'task_id' ));
}
?>
<#7>
<?php
$fields = array(
	'task_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'required_correctors' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'mutual_visibility' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '50',

	),
	'multi_color_highlight' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'max_points' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_correction_setting')) {
	$ilDB->createTable('xlet_correction_setting', $fields);
	$ilDB->addPrimaryKey('xlet_correction_setting', array( 'task_id' ));

	if (! $ilDB->sequenceExists('xlet_correction_setting')) {
		$ilDB->createSequence('xlet_correction_setting');
	}

}
?>
<#8>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'task_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'title' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),
	'message' => array(
		'notnull' => '1',
		'type' => 'clob',

	),
	'shown_from' => array(
		'type' => 'timestamp',

	),
	'shown_until' => array(
		'type' => 'timestamp',

	),

);
if (! $ilDB->tableExists('xlet_alert')) {
	$ilDB->createTable('xlet_alert', $fields);
	$ilDB->addPrimaryKey('xlet_alert', array( 'id' ));
	$ilDB->addIndex("xlet_alert", array("task_id"), "idx1");
	if (! $ilDB->sequenceExists('xlet_alert')) {
		$ilDB->createSequence('xlet_alert');
	}

}
?>
<#9>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'user_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'task_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'pseudonyme' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),
	'editor_font_size' => array(
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_participant')) {
	$ilDB->createTable('xlet_participant', $fields);
	$ilDB->addPrimaryKey('xlet_participant', array( 'id' ));
	$ilDB->addIndex("xlet_participant", array("user_id"), "idx1");
	$ilDB->addIndex("xlet_participant", array("task_id"), "idx2");

	if (! $ilDB->sequenceExists('xlet_participant')) {
		$ilDB->createSequence('xlet_participant');
	}

}
?>
<#10>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'user_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'task_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_corrector')) {
	$ilDB->createTable('xlet_corrector', $fields);
	$ilDB->addPrimaryKey('xlet_corrector', array( 'id' ));
	$ilDB->addIndex("xlet_corrector", array("user_id"), "idx1");
	$ilDB->addIndex("xlet_corrector", array("task_id"), "idx2");

	if (! $ilDB->sequenceExists('xlet_corrector')) {
		$ilDB->createSequence('xlet_corrector');
	}

}
?>