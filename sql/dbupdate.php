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
if (! $ilDB->tableExists('xlet_corr_setting')) {
	$ilDB->createTable('xlet_corr_setting', $fields);
	$ilDB->addPrimaryKey('xlet_corr_setting', array( 'task_id' ));

	if (! $ilDB->sequenceExists('xlet_corr_setting')) {
		$ilDB->createSequence('xlet_corr_setting');
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
	$ilDB->addIndex("xlet_alert", array("task_id"), "i1");
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
	'pseudonym' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),
	'editor_font_size' => array(
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_writer')) {
	$ilDB->createTable('xlet_writer', $fields);
	$ilDB->addPrimaryKey('xlet_writer', array( 'id' ));
	$ilDB->addIndex("xlet_writer", array("user_id", "task_id"), "i1");
	$ilDB->addIndex("xlet_writer", array("task_id"), "i2");

	if (! $ilDB->sequenceExists('xlet_writer')) {
		$ilDB->createSequence('xlet_writer');
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
	$ilDB->addIndex("xlet_corrector", array("user_id", "task_id"), "i1");
	$ilDB->addIndex("xlet_corrector", array("task_id"), "i2");

	if (! $ilDB->sequenceExists('xlet_corrector')) {
		$ilDB->createSequence('xlet_corrector');
	}

}
?>
<#11>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'uuid' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '50',

	),
	'writer_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'written_text' => array(
		'type' => 'clob',

	),
	'raw_text_hash' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '50',

	),
	'edit_started' => array(
		'type' => 'timestamp',

	),
	'edit_ended' => array(
		'type' => 'timestamp',

	),
	'processed_text' => array(
		'type' => 'clob',

	),
	'is_authorized' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'pdf_version' => array(
		'type' => 'integer',
		'length' => '4',

	),
	'final_points' => array(
		'type' => 'integer',
		'length' => '4',

	),
	'final_grade_level' => array(
		'type' => 'text',
		'length' => '255',

	),

);
if (! $ilDB->tableExists('xlet_essay')) {
	$ilDB->createTable('xlet_essay', $fields);
	$ilDB->addPrimaryKey('xlet_essay', array( 'id' ));
	$ilDB->addIndex("xlet_essay", array("uuid"), "i1");

	if (! $ilDB->sequenceExists('xlet_essay')) {
		$ilDB->createSequence('xlet_essay');
	}
}
?>
<#12>
<?php
if (! $ilDB->tableColumnExists('xlet_task_settings', 'solution')) {
    $ilDB->addTableColumn('xlet_task_settings', 'solution', ['type' => 'clob']);
}
?>
<#13>
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
	'notice_text' => array(
		'type' => 'clob',

	),
	'created' => array(
		'type' => 'timestamp',

	),

);
if (! $ilDB->tableExists('xlet_writer_notice')) {
	$ilDB->createTable('xlet_writer_notice', $fields);
	$ilDB->addPrimaryKey('xlet_writer_notice', array( 'id' ));
	$ilDB->addIndex("xlet_writer_notice", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_writer_notice')) {
		$ilDB->createSequence('xlet_writer_notice');
	}

}
?>
<#14>
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
	'comment' => array(
		'type' => 'clob',

	),
	'start_position' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'end_position' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_writer_comment')) {
	$ilDB->createTable('xlet_writer_comment', $fields);
	$ilDB->addPrimaryKey('xlet_writer_comment', array( 'id' ));
	$ilDB->addIndex("xlet_writer_comment", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_writer_comment')) {
		$ilDB->createSequence('xlet_writer_comment');
	}

}
?>
<#15>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'essay_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'timestamp' => array(
		'type' => 'timestamp',

	),
	'content' => array(
		'type' => 'clob',

	),
	'is_delta' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'hash_before' => array(
		'type' => 'text',
		'length' => '50',

	),
	'hash_after' => array(
		'type' => 'text',
		'length' => '50',

	),

);
if (! $ilDB->tableExists('xlet_writer_history')) {
	$ilDB->createTable('xlet_writer_history', $fields);
	$ilDB->addPrimaryKey('xlet_writer_history', array( 'id' ));
	$ilDB->addIndex("xlet_writer_history", array("essay_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_writer_history')) {
		$ilDB->createSequence('xlet_writer_history');
	}

}
?>
<#16>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'essay_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'corrector_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'summary_text' => array(
		'type' => 'clob',

	),
	'points' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'grade_level' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),

);
if (! $ilDB->tableExists('xlet_corrector_summary')) {
	$ilDB->createTable('xlet_corrector_summary', $fields);
	$ilDB->addPrimaryKey('xlet_corrector_summary', array( 'id' ));
	$ilDB->addIndex("xlet_corrector_summary", array("essay_id"), "i1");
	$ilDB->addIndex("xlet_corrector_summary", array("essay_id", "corrector_id"), "i2");

	if (! $ilDB->sequenceExists('xlet_corrector_summary')) {
		$ilDB->createSequence('xlet_corrector_summary');
	}

}
?>
<#17>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'essay_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'comment' => array(
		'type' => 'clob',

	),
	'start_position' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'end_position' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'points' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'rating' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '16',

	),

);
if (! $ilDB->tableExists('xlet_corrector_comment')) {
	$ilDB->createTable('xlet_corrector_comment', $fields);
	$ilDB->addPrimaryKey('xlet_corrector_comment', array( 'id' ));
	$ilDB->addIndex("xlet_corrector_comment", array("essay_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_corrector_comment')) {
		$ilDB->createSequence('xlet_corrector_comment');
	}

}
?>
<#18>
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
	'essay_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'token' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '50',

	),
	'ip' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '50',

	),
	'valid_until' => array(
		'type' => 'timestamp',

	),

);
if (! $ilDB->tableExists('xlet_access_token')) {
	$ilDB->createTable('xlet_access_token', $fields);
	$ilDB->addPrimaryKey('xlet_access_token', array( 'id' ));
	$ilDB->addIndex("xlet_access_token", array("user_id", "essay_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_access_token')) {
		$ilDB->createSequence('xlet_access_token');
	}

}
?>
<#19>
<?php
if (! $ilDB->tableColumnExists('xlet_corrector_comment', 'corrector_id')) {
	$ilDB->addTableColumn('xlet_corrector_comment', 'corrector_id', array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',
	));
}
?>
<#20>
<?php
if ($ilDB->indexExistsByFields('xlet_access_token', array("user_id", "essay_id"))) {
    $ilDB->dropIndexByFields('xlet_access_token', array("user_id", "essay_id"));
}
if ($ilDB->tableColumnExists('xlet_access_token', 'essay_id')) {
    $ilDB->dropTableColumn('xlet_access_token', 'essay_id');
}
if (!$ilDB->tableColumnExists('xlet_access_token','task_id')) {
    $ilDB->addTableColumn('xlet_access_token','task_id', [
    'notnull' => '1',
    'type' => 'integer',
    'length' => '4',
    ]);
}
if (!$ilDB->indexExistsByFields('xlet_access_token', array('user_id'))) {
    $ilDB->addIndex("xlet_access_token", array("user_id"), "i1");
}
if (!$ilDB->indexExistsByFields('xlet_access_token', array('task_id'))) {
    $ilDB->addIndex("xlet_access_token", array("task_id"), "i2");
}
if (!$ilDB->indexExistsByFields('xlet_access_token', array('valid_until'))) {
    $ilDB->addIndex("xlet_access_token", array("valid_until"), "i3");
}
?>
<#21>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'writer_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'corrector_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'position' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_corrector_ass')) {
	$ilDB->createTable('xlet_corrector_ass', $fields);
	$ilDB->addPrimaryKey('xlet_corrector_ass', array( 'id' ));
	$ilDB->addIndex("xlet_corrector_ass", array("writer_id", "corrector_id"), "i1");
	$ilDB->addIndex("xlet_corrector_ass", array("corrector_id", "writer_id"), "i2");

	if (! $ilDB->sequenceExists('xlet_corrector_ass')) {
		$ilDB->createSequence('xlet_corrector_ass');
	}

}
?>
<#22>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'writer_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'task_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'minutes' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_time_extension')) {
	$ilDB->createTable('xlet_time_extension', $fields);
	$ilDB->addPrimaryKey('xlet_time_extension', array( 'id' ));
	$ilDB->addIndex("xlet_time_extension", array("writer_id", "task_id"), "i1");
	$ilDB->addIndex("xlet_time_extension", array("task_id"), "i2");

	if (! $ilDB->sequenceExists('xlet_time_extension')) {
		$ilDB->createSequence('xlet_time_extension');
	}

}
?>
<#23>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'object_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'min_points' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'grade' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),

);
if (! $ilDB->tableExists('xlet_grade_level')) {
	$ilDB->createTable('xlet_grade_level', $fields);
	$ilDB->addPrimaryKey('xlet_grade_level', array( 'id' ));
	$ilDB->addIndex("xlet_grade_level", array("object_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_grade_level')) {
		$ilDB->createSequence('xlet_grade_level');
	}

}
?>
<#24>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'object_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'title' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),
	'description' => array(
		'type' => 'clob',

	),
	'points' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_rating_crit')) {
	$ilDB->createTable('xlet_rating_crit', $fields);
	$ilDB->addPrimaryKey('xlet_rating_crit', array( 'id' ));
	$ilDB->addIndex("xlet_rating_crit", array("object_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_rating_crit')) {
		$ilDB->createSequence('xlet_rating_crit');
	}

}
?>
<#25>
<?php
$fields = array(
	'id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'rating_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'corr_comment_id' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),
	'points' => array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',

	),

);
if (! $ilDB->tableExists('xlet_crit_points')) {
	$ilDB->createTable('xlet_crit_points', $fields);
	$ilDB->addPrimaryKey('xlet_crit_points', array( 'id' ));
	$ilDB->addIndex("xlet_crit_points", array("rating_id", "corr_comment_id"), "i1");
	$ilDB->addIndex("xlet_crit_points", array("corr_comment_id"), "i2");

	if (! $ilDB->sequenceExists('xlet_crit_points')) {
		$ilDB->createSequence('xlet_crit_points');
	}

}
?>
<#26>
<?php
if(!$ilDB->indexExistsByFields("xlet_essay", array("writer_id")))
{
    $ilDB->addIndex("xlet_essay", array("writer_id"), "i2");
}

if (!$ilDB->tableColumnExists('xlet_essay','task_id')) {
    $ilDB->addTableColumn('xlet_essay','task_id', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ]);
    $ilDB->addIndex("xlet_essay", array("task_id"), "i3");
}

?>
<#27>
<?php

if ($ilDB->tableColumnExists('xlet_access_token','task_id')) {
    $ilDB->renameTableColumn('xlet_access_token', 'task_id', 'essay_id');
}
?>
<#28>
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
    'description' => array(
        'type' => 'clob',

    ),
    'file_id' => array(
        'type' => 'integer',
        'length' => '4',

    ),
    'url' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '4000',

    ),
    'type' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',

    ),
    'availability' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',

    ),

);
if (! $ilDB->tableExists('xlet_resource')) {
    $ilDB->createTable('xlet_resource', $fields);
    $ilDB->addPrimaryKey('xlet_resource', array( 'id' ));
    $ilDB->addIndex("xlet_resource", array("task_id"), "i1");

    if (! $ilDB->sequenceExists('xlet_resource')) {
        $ilDB->createSequence('xlet_resource');
    }
}
?>
<#29>
<?php
if ($ilDB->tableColumnExists('xlet_access_token','essay_id')) {
    $ilDB->renameTableColumn('xlet_access_token', 'essay_id', 'task_id');
}
?>
<#30>
<?php
if ($ilDB->indexExistsByFields('xlet_writer_history', ['hash_before'])) {
    $ilDB->addIndex('xlet_writer_history', ['hash_before'], 'i2');
}
if ($ilDB->indexExistsByFields('xlet_writer_history', ['hash_after'])) {
    $ilDB->addIndex('xlet_writer_history', ['hash_after'], 'i3');
}
?>
<#31>
<?php
if ($ilDB->tableColumnExists('xlet_resource','file_id')) {
    $ilDB->modifyTableColumn('xlet_resource','file_id', [
        'notnull' => '0',
        'type' => 'text',
        'length' => '50',
    ]);
    $ilDB->addIndex("xlet_resource", array("file_id"), "i2");
}
?>
<#32>
<?php
$ilDB->dropPrimaryKey("xlet_resource");
$ilDB->addPrimaryKey("xlet_resource", array("id"));
?>
<#33>
<?php
if (!$ilDB->tableColumnExists('xlet_access_token','purpose')) {
    $ilDB->addTableColumn('xlet_access_token', 'purpose', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',
        'default' => 'data'
    ]);
}
?>
<#34>
<?php
if (!$ilDB->tableColumnExists('xlet_corrector_summary','grade_level_id')) {
    $ilDB->addTableColumn('xlet_corrector_summary', 'grade_level_id', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
    $ilDB->dropTableColumn('xlet_corrector_summary', 'grade_level');
}
?>
<#35>
<?php
    $ilDB->modifyTableColumn('xlet_corrector_summary','points', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => null
    ]);
?>
<#36>
<?php
if (!$ilDB->tableColumnExists('xlet_essay','final_grade_level_id')) {
    $ilDB->addTableColumn('xlet_essay', 'final_grade_level_id', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
    $ilDB->dropTableColumn('xlet_essay', 'final_grade_level');
}
?>
<#37>
<?php
if (!$ilDB->tableColumnExists('xlet_essay','writing_authorized')) {
    $ilDB->addTableColumn('xlet_essay', 'writing_authorized', [
        'type' => 'timestamp',
    ]);
    $ilDB->dropTableColumn('xlet_essay', 'is_authorized');
}
if (!$ilDB->tableColumnExists('xlet_essay','writing_authorized_by')) {
    $ilDB->addTableColumn('xlet_essay', 'writing_authorized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
}
?>
<#38>
<?php
if (!$ilDB->tableColumnExists('xlet_essay','correction_finalized')) {
    $ilDB->addTableColumn('xlet_essay', 'correction_finalized', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlet_essay','correction_finalized_by')) {
    $ilDB->addTableColumn('xlet_essay', 'correction_finalized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
}
?>
<#39>
<?php
    $ilDB->modifyTableColumn('xlet_essay', 'final_points', [
        'type' => 'float'
    ]);
?>
<#40>
<?php
if (!$ilDB->tableColumnExists('xlet_corrector_summary','last_change')) {
    $ilDB->addTableColumn('xlet_essay', 'last_change', [
        'type' => 'timestamp',
    ]);
}
?>
<#41>
<?php
if (!$ilDB->tableColumnExists('xlet_corrector_summary','correction_authorized')) {
    $ilDB->addTableColumn('xlet_essay', 'correction_authorized', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlet_corrector_summary','correction_authorized_by')) {
    $ilDB->addTableColumn('xlet_essay', 'correction_authorized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
}
?>
<#42>
<?php
if (!$ilDB->tableColumnExists('xlet_corr_setting','max_auto_distance')) {
    $ilDB->addTableColumn('xlet_corr_setting', 'max_auto_distance', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => 0
    ]);
}
?>
<#43>
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
	'timestamp' => array(
		'type' => 'timestamp',

	),
	'category' => array(
		'notnull' => '1',
		'type' => 'text',
		'length' => '255',

	),
	'entry' => array(
		'type' => 'clob',

	),

);
if (! $ilDB->tableExists('xlet_log_entry')) {
	$ilDB->createTable('xlet_log_entry', $fields);
	$ilDB->addPrimaryKey('xlet_log_entry', array( 'id' ));
	$ilDB->addIndex("xlet_log_entry", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlet_log_entry')) {
		$ilDB->createSequence('xlet_log_entry');
	}

}
?>
<#44>
<?php
//
//if (!$ilDB->tableColumnExists('xlet_log_entry','title')) {
//	$ilDB->addTableColumn('xlet_log_entry', 'title', [
//		'notnull' => '1',
//		'type' => 'text',
//		'length' => '255',
//	]);
//}
?>
<#45>
<?php
//
//if (!$ilDB->tableColumnExists('xlet_writer_notice','title')) {
//	$ilDB->addTableColumn('xlet_writer_notice', 'title', [
//		'notnull' => '1',
//		'type' => 'text',
//		'length' => '255',
//	]);
//}
if (!$ilDB->tableColumnExists('xlet_writer_notice','writer_id')) {
	$ilDB->addTableColumn('xlet_writer_notice', 'writer_id', [
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',
	]);
	$ilDB->addIndex("xlet_writer_notice", array("writer_id"), "i2");
}
?>
<#46>
<?php

if ($ilDB->tableColumnExists('xlet_writer_notice','writer_id')) {
	$ilDB->modifyTableColumn('xlet_writer_notice', 'writer_id', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4',
	]);
}
?>
<#47>
<?php

if (!$ilDB->tableColumnExists('xlet_alert','writer_id')) {
	$ilDB->addTableColumn('xlet_alert', 'writer_id', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4',
	]);
	$ilDB->addIndex("xlet_alert", array("writer_id"), "i2");
}

if ($ilDB->tableColumnExists('xlet_alert','title')) {
	$ilDB->modifyTableColumn('xlet_alert', 'title', [
		'notnull' => '0',
		'type' => 'text',
		'length' => '255',
	]);
}

?>
<#48>
<?php
if (!$ilDB->tableColumnExists('xlet_essay','writing_excluded')) {
	$ilDB->addTableColumn('xlet_essay', 'writing_excluded', [
		'type' => 'timestamp',
	]);
}
if (!$ilDB->tableColumnExists('xlet_essay','writing_excluded_by')) {
	$ilDB->addTableColumn('xlet_essay', 'writing_excluded_by', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4'
	]);
}
?>
<#49>
<?php
if (!$ilDB->tableColumnExists('xlet_grade_level','code')) {
	$ilDB->addTableColumn('xlet_grade_level', 'code', [
        'notnull' => '0',
        'type' => 'text',
        'length' => '255',
	]);
}

if (!$ilDB->tableColumnExists('xlet_grade_level','passed')) {
	$ilDB->addTableColumn('xlet_grade_level', 'passed', [
		'notnull' => '1',
		'type' => 'integer',
		'length' => '1',
	]);
}

?>
<#50>
<?php
if (!$ilDB->tableColumnExists('xlet_corrector_summary','last_change')) {
    $ilDB->addTableColumn('xlet_corrector_summary', 'last_change', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlet_corrector_summary','correction_authorized')) {
    $ilDB->addTableColumn('xlet_corrector_summary', 'correction_authorized', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlet_corrector_summary','correction_authorized_by')) {
    $ilDB->addTableColumn('xlet_corrector_summary', 'correction_authorized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => 4,
    ]);
}

?>
<#51>
<?php
if ($ilDB->tableColumnExists('xlet_corr_setting','max_auto_distance')) {
    $ilDB->modifyTableColumn('xlet_corr_setting', 'max_auto_distance', [
        'notnull' => '1',
        'type' => 'float',
        'default' => 0
    ]);
}
?>
<#52>
<?php
if ($ilDB->tableColumnExists('xlet_corr_setting','max_auto_distance')) {
    $ilDB->modifyTableColumn('xlet_corr_setting', 'max_auto_distance', [
        'notnull' => '1',
        'type' => 'float',
        'default' => 0
    ]);
}
?>
<#53>
<?php
if ($ilDB->tableColumnExists('xlet_corr_setting','mutual_visibility')) {
    $ilDB->dropTableColumn('xlet_corr_setting', 'mutual_visibility');
}
$ilDB->addTableColumn('xlet_corr_setting', 'mutual_visibility', [
    'notnull' => '1',
    'type' => 'integer',
    'length' => 4,
    'default' => 0
]);
?>
<#54>
<?php
if (!$ilDB->tableColumnExists('xlet_corr_setting','assign_mode')) {
    $ilDB->addTableColumn('xlet_corr_setting', 'assign_mode', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',
        'default' => 'random_equal'
    ]);
}
?>
<#55>
<?php
if ($ilDB->tableColumnExists('xlet_grade_level','min_points')) {
	$ilDB->modifyTableColumn('xlet_grade_level', 'min_points', array(
		'notnull' => '1',
		'type' => 'float'
	));
}
?>
<#56>
<?php
if ($ilDB->tableColumnExists('xlet_corrector_summary','points')) {
    $ilDB->modifyTableColumn('xlet_corrector_summary', 'points', array(
        'notnull' => '0',
        'type' => 'float'
    ));
}
?>
<#57>
<?php
if (!$ilDB->tableColumnExists('xlet_plugin_config','primary_color')) {
    $ilDB->addTableColumn('xlet_plugin_config', 'primary_color', array(
        'type' => 'text',
        'length' => '250'
    ));
}
?>
<#58>
<?php
if (!$ilDB->tableColumnExists('xlet_plugin_config','primary_text_color')) {
    $ilDB->addTableColumn('xlet_plugin_config', 'primary_text_color', array(
        'type' => 'text',
        'length' => '250'
    ));
}
?>
<#59>
<?php
if (!$ilDB->tableColumnExists('xlet_essay','stitch_comment')) {
    $ilDB->addTableColumn('xlet_essay', 'stitch_comment', array(
        'type' => 'clob'
    ));
}
?>
<#60>
<?php
if (!$ilDB->tableColumnExists('xlet_task_settings','keep_essay_available')) {
    $ilDB->addTableColumn('xlet_task_settings', 'keep_essay_available', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
if (!$ilDB->tableColumnExists('xlet_task_settings','solution_available_date')) {
    $ilDB->addTableColumn('xlet_task_settings', 'solution_available_date', array(
        'type' => 'timestamp'
    ));
}
if (!$ilDB->tableColumnExists('xlet_task_settings','result_available_type')) {
    $ilDB->addTableColumn('xlet_task_settings', 'result_available_type', array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',
        'default' => 'review'
    ));
}
if (!$ilDB->tableColumnExists('xlet_task_settings','result_available_date')) {
    $ilDB->addTableColumn('xlet_task_settings', 'result_available_date', array(
        'type' => 'timestamp'
    ));
}

?>
<#61>
<?php
if (!$ilDB->tableColumnExists('xlet_task_settings','solution_available')) {
    $ilDB->addTableColumn('xlet_task_settings', 'solution_available', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#62>
<?php
if (!$ilDB->tableColumnExists('xlet_plugin_config','simulate_offline')) {
    $ilDB->addTableColumn('xlet_plugin_config', 'simulate_offline', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
