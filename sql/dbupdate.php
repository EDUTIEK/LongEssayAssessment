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
$type_id = ilDBUpdateNewObjectType::addNewType('xlas', 'Long Essay Task');
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
if (! $ilDB->tableExists('xlas_plugin_config')) {
    $ilDB->createTable('xlas_plugin_config', $fields);
    $ilDB->addPrimaryKey('xlas_plugin_config', array( 'id' ));
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
if (! $ilDB->tableExists('xlas_object_settings')) {
    $ilDB->createTable('xlas_object_settings', $fields);
    $ilDB->addPrimaryKey('xlas_object_settings', array( 'obj_id' ));}
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
if (! $ilDB->tableExists('xlas_task_settings')) {
    $ilDB->createTable('xlas_task_settings', $fields);
    $ilDB->addPrimaryKey('xlas_task_settings', array( 'task_id' ));
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
if (! $ilDB->tableExists('xlas_editor_settings')) {
    $ilDB->createTable('xlas_editor_settings', $fields);
    $ilDB->addPrimaryKey('xlas_editor_settings', array( 'task_id' ));
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
if (! $ilDB->tableExists('xlas_corr_setting')) {
	$ilDB->createTable('xlas_corr_setting', $fields);
	$ilDB->addPrimaryKey('xlas_corr_setting', array( 'task_id' ));

	if (! $ilDB->sequenceExists('xlas_corr_setting')) {
		$ilDB->createSequence('xlas_corr_setting');
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
if (! $ilDB->tableExists('xlas_alert')) {
	$ilDB->createTable('xlas_alert', $fields);
	$ilDB->addPrimaryKey('xlas_alert', array( 'id' ));
	$ilDB->addIndex("xlas_alert", array("task_id"), "i1");
	if (! $ilDB->sequenceExists('xlas_alert')) {
		$ilDB->createSequence('xlas_alert');
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
if (! $ilDB->tableExists('xlas_writer')) {
	$ilDB->createTable('xlas_writer', $fields);
	$ilDB->addPrimaryKey('xlas_writer', array( 'id' ));
	$ilDB->addIndex("xlas_writer", array("user_id", "task_id"), "i1");
	$ilDB->addIndex("xlas_writer", array("task_id"), "i2");

	if (! $ilDB->sequenceExists('xlas_writer')) {
		$ilDB->createSequence('xlas_writer');
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
if (! $ilDB->tableExists('xlas_corrector')) {
	$ilDB->createTable('xlas_corrector', $fields);
	$ilDB->addPrimaryKey('xlas_corrector', array( 'id' ));
	$ilDB->addIndex("xlas_corrector", array("user_id", "task_id"), "i1");
	$ilDB->addIndex("xlas_corrector", array("task_id"), "i2");

	if (! $ilDB->sequenceExists('xlas_corrector')) {
		$ilDB->createSequence('xlas_corrector');
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
if (! $ilDB->tableExists('xlas_essay')) {
	$ilDB->createTable('xlas_essay', $fields);
	$ilDB->addPrimaryKey('xlas_essay', array( 'id' ));
	$ilDB->addIndex("xlas_essay", array("uuid"), "i1");

	if (! $ilDB->sequenceExists('xlas_essay')) {
		$ilDB->createSequence('xlas_essay');
	}
}
?>
<#12>
<?php
if (! $ilDB->tableColumnExists('xlas_task_settings', 'solution')) {
    $ilDB->addTableColumn('xlas_task_settings', 'solution', ['type' => 'clob']);
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
if (! $ilDB->tableExists('xlas_writer_notice')) {
	$ilDB->createTable('xlas_writer_notice', $fields);
	$ilDB->addPrimaryKey('xlas_writer_notice', array( 'id' ));
	$ilDB->addIndex("xlas_writer_notice", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_writer_notice')) {
		$ilDB->createSequence('xlas_writer_notice');
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
if (! $ilDB->tableExists('xlas_writer_comment')) {
	$ilDB->createTable('xlas_writer_comment', $fields);
	$ilDB->addPrimaryKey('xlas_writer_comment', array( 'id' ));
	$ilDB->addIndex("xlas_writer_comment", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_writer_comment')) {
		$ilDB->createSequence('xlas_writer_comment');
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
if (! $ilDB->tableExists('xlas_writer_history')) {
	$ilDB->createTable('xlas_writer_history', $fields);
	$ilDB->addPrimaryKey('xlas_writer_history', array( 'id' ));
	$ilDB->addIndex("xlas_writer_history", array("essay_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_writer_history')) {
		$ilDB->createSequence('xlas_writer_history');
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
if (! $ilDB->tableExists('xlas_corrector_summary')) {
	$ilDB->createTable('xlas_corrector_summary', $fields);
	$ilDB->addPrimaryKey('xlas_corrector_summary', array( 'id' ));
	$ilDB->addIndex("xlas_corrector_summary", array("essay_id"), "i1");
	$ilDB->addIndex("xlas_corrector_summary", array("essay_id", "corrector_id"), "i2");

	if (! $ilDB->sequenceExists('xlas_corrector_summary')) {
		$ilDB->createSequence('xlas_corrector_summary');
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
if (! $ilDB->tableExists('xlas_corrector_comment')) {
	$ilDB->createTable('xlas_corrector_comment', $fields);
	$ilDB->addPrimaryKey('xlas_corrector_comment', array( 'id' ));
	$ilDB->addIndex("xlas_corrector_comment", array("essay_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_corrector_comment')) {
		$ilDB->createSequence('xlas_corrector_comment');
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
if (! $ilDB->tableExists('xlas_access_token')) {
	$ilDB->createTable('xlas_access_token', $fields);
	$ilDB->addPrimaryKey('xlas_access_token', array( 'id' ));
	$ilDB->addIndex("xlas_access_token", array("user_id", "essay_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_access_token')) {
		$ilDB->createSequence('xlas_access_token');
	}

}
?>
<#19>
<?php
if (! $ilDB->tableColumnExists('xlas_corrector_comment', 'corrector_id')) {
	$ilDB->addTableColumn('xlas_corrector_comment', 'corrector_id', array(
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',
	));
}
?>
<#20>
<?php
if ($ilDB->indexExistsByFields('xlas_access_token', array("user_id", "essay_id"))) {
    $ilDB->dropIndexByFields('xlas_access_token', array("user_id", "essay_id"));
}
if ($ilDB->tableColumnExists('xlas_access_token', 'essay_id')) {
    $ilDB->dropTableColumn('xlas_access_token', 'essay_id');
}
if (!$ilDB->tableColumnExists('xlas_access_token','task_id')) {
    $ilDB->addTableColumn('xlas_access_token','task_id', [
    'notnull' => '1',
    'type' => 'integer',
    'length' => '4',
    ]);
}
if (!$ilDB->indexExistsByFields('xlas_access_token', array('user_id'))) {
    $ilDB->addIndex("xlas_access_token", array("user_id"), "i1");
}
if (!$ilDB->indexExistsByFields('xlas_access_token', array('task_id'))) {
    $ilDB->addIndex("xlas_access_token", array("task_id"), "i2");
}
if (!$ilDB->indexExistsByFields('xlas_access_token', array('valid_until'))) {
    $ilDB->addIndex("xlas_access_token", array("valid_until"), "i3");
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
if (! $ilDB->tableExists('xlas_corrector_ass')) {
	$ilDB->createTable('xlas_corrector_ass', $fields);
	$ilDB->addPrimaryKey('xlas_corrector_ass', array( 'id' ));
	$ilDB->addIndex("xlas_corrector_ass", array("writer_id", "corrector_id"), "i1");
	$ilDB->addIndex("xlas_corrector_ass", array("corrector_id", "writer_id"), "i2");

	if (! $ilDB->sequenceExists('xlas_corrector_ass')) {
		$ilDB->createSequence('xlas_corrector_ass');
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
if (! $ilDB->tableExists('xlas_time_extension')) {
	$ilDB->createTable('xlas_time_extension', $fields);
	$ilDB->addPrimaryKey('xlas_time_extension', array( 'id' ));
	$ilDB->addIndex("xlas_time_extension", array("writer_id", "task_id"), "i1");
	$ilDB->addIndex("xlas_time_extension", array("task_id"), "i2");

	if (! $ilDB->sequenceExists('xlas_time_extension')) {
		$ilDB->createSequence('xlas_time_extension');
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
if (! $ilDB->tableExists('xlas_grade_level')) {
	$ilDB->createTable('xlas_grade_level', $fields);
	$ilDB->addPrimaryKey('xlas_grade_level', array( 'id' ));
	$ilDB->addIndex("xlas_grade_level", array("object_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_grade_level')) {
		$ilDB->createSequence('xlas_grade_level');
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
if (! $ilDB->tableExists('xlas_rating_crit')) {
	$ilDB->createTable('xlas_rating_crit', $fields);
	$ilDB->addPrimaryKey('xlas_rating_crit', array( 'id' ));
	$ilDB->addIndex("xlas_rating_crit", array("object_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_rating_crit')) {
		$ilDB->createSequence('xlas_rating_crit');
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
if (! $ilDB->tableExists('xlas_crit_points')) {
	$ilDB->createTable('xlas_crit_points', $fields);
	$ilDB->addPrimaryKey('xlas_crit_points', array( 'id' ));
	$ilDB->addIndex("xlas_crit_points", array("rating_id", "corr_comment_id"), "i1");
	$ilDB->addIndex("xlas_crit_points", array("corr_comment_id"), "i2");

	if (! $ilDB->sequenceExists('xlas_crit_points')) {
		$ilDB->createSequence('xlas_crit_points');
	}

}
?>
<#26>
<?php
if(!$ilDB->indexExistsByFields("xlas_essay", array("writer_id")))
{
    $ilDB->addIndex("xlas_essay", array("writer_id"), "i2");
}

if (!$ilDB->tableColumnExists('xlas_essay','task_id')) {
    $ilDB->addTableColumn('xlas_essay','task_id', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ]);
    $ilDB->addIndex("xlas_essay", array("task_id"), "i3");
}

?>
<#27>
<?php

if ($ilDB->tableColumnExists('xlas_access_token','task_id')) {
    $ilDB->renameTableColumn('xlas_access_token', 'task_id', 'essay_id');
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
if (! $ilDB->tableExists('xlas_resource')) {
    $ilDB->createTable('xlas_resource', $fields);
    $ilDB->addPrimaryKey('xlas_resource', array( 'id' ));
    $ilDB->addIndex("xlas_resource", array("task_id"), "i1");

    if (! $ilDB->sequenceExists('xlas_resource')) {
        $ilDB->createSequence('xlas_resource');
    }
}
?>
<#29>
<?php
if ($ilDB->tableColumnExists('xlas_access_token','essay_id')) {
    $ilDB->renameTableColumn('xlas_access_token', 'essay_id', 'task_id');
}
?>
<#30>
<?php
if ($ilDB->indexExistsByFields('xlas_writer_history', ['hash_before'])) {
    $ilDB->addIndex('xlas_writer_history', ['hash_before'], 'i2');
}
if ($ilDB->indexExistsByFields('xlas_writer_history', ['hash_after'])) {
    $ilDB->addIndex('xlas_writer_history', ['hash_after'], 'i3');
}
?>
<#31>
<?php
if ($ilDB->tableColumnExists('xlas_resource','file_id')) {
    $ilDB->modifyTableColumn('xlas_resource','file_id', [
        'notnull' => '0',
        'type' => 'text',
        'length' => '50',
    ]);
    $ilDB->addIndex("xlas_resource", array("file_id"), "i2");
}
?>
<#32>
<?php
$ilDB->dropPrimaryKey("xlas_resource");
$ilDB->addPrimaryKey("xlas_resource", array("id"));
?>
<#33>
<?php
if (!$ilDB->tableColumnExists('xlas_access_token','purpose')) {
    $ilDB->addTableColumn('xlas_access_token', 'purpose', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',
        'default' => 'data'
    ]);
}
?>
<#34>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_summary','grade_level_id')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'grade_level_id', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
    $ilDB->dropTableColumn('xlas_corrector_summary', 'grade_level');
}
?>
<#35>
<?php
    $ilDB->modifyTableColumn('xlas_corrector_summary','points', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => null
    ]);
?>
<#36>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','final_grade_level_id')) {
    $ilDB->addTableColumn('xlas_essay', 'final_grade_level_id', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
    $ilDB->dropTableColumn('xlas_essay', 'final_grade_level');
}
?>
<#37>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','writing_authorized')) {
    $ilDB->addTableColumn('xlas_essay', 'writing_authorized', [
        'type' => 'timestamp',
    ]);
    $ilDB->dropTableColumn('xlas_essay', 'is_authorized');
}
if (!$ilDB->tableColumnExists('xlas_essay','writing_authorized_by')) {
    $ilDB->addTableColumn('xlas_essay', 'writing_authorized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
}
?>
<#38>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','correction_finalized')) {
    $ilDB->addTableColumn('xlas_essay', 'correction_finalized', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlas_essay','correction_finalized_by')) {
    $ilDB->addTableColumn('xlas_essay', 'correction_finalized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4'
    ]);
}
?>
<#39>
<?php
    $ilDB->modifyTableColumn('xlas_essay', 'final_points', [
        'type' => 'float'
    ]);
?>
<#40>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_summary','last_change')) {
    $ilDB->addTableColumn('xlas_essay', 'last_change', [
        'type' => 'timestamp',
    ]);
}
?>
<#41>
<?php
// obsolete
?>
<#42>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','max_auto_distance')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'max_auto_distance', [
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
if (! $ilDB->tableExists('xlas_log_entry')) {
	$ilDB->createTable('xlas_log_entry', $fields);
	$ilDB->addPrimaryKey('xlas_log_entry', array( 'id' ));
	$ilDB->addIndex("xlas_log_entry", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_log_entry')) {
		$ilDB->createSequence('xlas_log_entry');
	}

}
?>
<#44>
<?php
//
//if (!$ilDB->tableColumnExists('xlas_log_entry','title')) {
//	$ilDB->addTableColumn('xlas_log_entry', 'title', [
//		'notnull' => '1',
//		'type' => 'text',
//		'length' => '255',
//	]);
//}
?>
<#45>
<?php
//
//if (!$ilDB->tableColumnExists('xlas_writer_notice','title')) {
//	$ilDB->addTableColumn('xlas_writer_notice', 'title', [
//		'notnull' => '1',
//		'type' => 'text',
//		'length' => '255',
//	]);
//}
if (!$ilDB->tableColumnExists('xlas_writer_notice','writer_id')) {
	$ilDB->addTableColumn('xlas_writer_notice', 'writer_id', [
		'notnull' => '1',
		'type' => 'integer',
		'length' => '4',
	]);
	$ilDB->addIndex("xlas_writer_notice", array("writer_id"), "i2");
}
?>
<#46>
<?php

if ($ilDB->tableColumnExists('xlas_writer_notice','writer_id')) {
	$ilDB->modifyTableColumn('xlas_writer_notice', 'writer_id', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4',
	]);
}
?>
<#47>
<?php

if (!$ilDB->tableColumnExists('xlas_alert','writer_id')) {
	$ilDB->addTableColumn('xlas_alert', 'writer_id', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4',
	]);
	$ilDB->addIndex("xlas_alert", array("writer_id"), "i2");
}

if ($ilDB->tableColumnExists('xlas_alert','title')) {
	$ilDB->modifyTableColumn('xlas_alert', 'title', [
		'notnull' => '0',
		'type' => 'text',
		'length' => '255',
	]);
}

?>
<#48>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','writing_excluded')) {
	$ilDB->addTableColumn('xlas_essay', 'writing_excluded', [
		'type' => 'timestamp',
	]);
}
if (!$ilDB->tableColumnExists('xlas_essay','writing_excluded_by')) {
	$ilDB->addTableColumn('xlas_essay', 'writing_excluded_by', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4'
	]);
}
?>
<#49>
<?php
if (!$ilDB->tableColumnExists('xlas_grade_level','code')) {
	$ilDB->addTableColumn('xlas_grade_level', 'code', [
        'notnull' => '0',
        'type' => 'text',
        'length' => '255',
	]);
}

if (!$ilDB->tableColumnExists('xlas_grade_level','passed')) {
	$ilDB->addTableColumn('xlas_grade_level', 'passed', [
		'notnull' => '1',
		'type' => 'integer',
		'length' => '1',
	]);
}

?>
<#50>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_summary','last_change')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'last_change', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corrector_summary','correction_authorized')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'correction_authorized', [
        'type' => 'timestamp',
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corrector_summary','correction_authorized_by')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'correction_authorized_by', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => 4,
    ]);
}

?>
<#51>
<?php
if ($ilDB->tableColumnExists('xlas_corr_setting','max_auto_distance')) {
    $ilDB->modifyTableColumn('xlas_corr_setting', 'max_auto_distance', [
        'notnull' => '1',
        'type' => 'float',
        'default' => 0
    ]);
}
?>
<#52>
<?php
if ($ilDB->tableColumnExists('xlas_corr_setting','max_auto_distance')) {
    $ilDB->modifyTableColumn('xlas_corr_setting', 'max_auto_distance', [
        'notnull' => '1',
        'type' => 'float',
        'default' => 0
    ]);
}
?>
<#53>
<?php
if ($ilDB->tableColumnExists('xlas_corr_setting','mutual_visibility')) {
    $ilDB->dropTableColumn('xlas_corr_setting', 'mutual_visibility');
}
$ilDB->addTableColumn('xlas_corr_setting', 'mutual_visibility', [
    'notnull' => '1',
    'type' => 'integer',
    'length' => 4,
    'default' => 0
]);
?>
<#54>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','assign_mode')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'assign_mode', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',
        'default' => 'random_equal'
    ]);
}
?>
<#55>
<?php
if ($ilDB->tableColumnExists('xlas_grade_level','min_points')) {
	$ilDB->modifyTableColumn('xlas_grade_level', 'min_points', array(
		'notnull' => '1',
		'type' => 'float'
	));
}
?>
<#56>
<?php
if ($ilDB->tableColumnExists('xlas_corrector_summary','points')) {
    $ilDB->modifyTableColumn('xlas_corrector_summary', 'points', array(
        'notnull' => '0',
        'type' => 'float'
    ));
}
?>
<#57>
<?php
if (!$ilDB->tableColumnExists('xlas_plugin_config','primary_color')) {
    $ilDB->addTableColumn('xlas_plugin_config', 'primary_color', array(
        'type' => 'text',
        'length' => '250'
    ));
}
?>
<#58>
<?php
if (!$ilDB->tableColumnExists('xlas_plugin_config','primary_text_color')) {
    $ilDB->addTableColumn('xlas_plugin_config', 'primary_text_color', array(
        'type' => 'text',
        'length' => '250'
    ));
}
?>
<#59>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','stitch_comment')) {
    $ilDB->addTableColumn('xlas_essay', 'stitch_comment', array(
        'type' => 'clob'
    ));
}
?>
<#60>
<?php
if (!$ilDB->tableColumnExists('xlas_task_settings','keep_essay_available')) {
    $ilDB->addTableColumn('xlas_task_settings', 'keep_essay_available', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
if (!$ilDB->tableColumnExists('xlas_task_settings','solution_available_date')) {
    $ilDB->addTableColumn('xlas_task_settings', 'solution_available_date', array(
        'type' => 'timestamp'
    ));
}
if (!$ilDB->tableColumnExists('xlas_task_settings','result_available_type')) {
    $ilDB->addTableColumn('xlas_task_settings', 'result_available_type', array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '10',
        'default' => 'review'
    ));
}
if (!$ilDB->tableColumnExists('xlas_task_settings','result_available_date')) {
    $ilDB->addTableColumn('xlas_task_settings', 'result_available_date', array(
        'type' => 'timestamp'
    ));
}

?>
<#61>
<?php
if (!$ilDB->tableColumnExists('xlas_task_settings','solution_available')) {
    $ilDB->addTableColumn('xlas_task_settings', 'solution_available', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#62>
<?php
if (!$ilDB->tableColumnExists('xlas_plugin_config','simulate_offline')) {
    $ilDB->addTableColumn('xlas_plugin_config', 'simulate_offline', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#63>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','stitch_when_distance')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'stitch_when_distance', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 1
    ));
}
?>
<#64>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','stitch_when_decimals')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'stitch_when_decimals', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#65>
<?php
if ($ilDB->tableColumnExists('xlas_crit_points','rating_id')) {
    $ilDB->renameTableColumn('xlas_crit_points', 'rating_id', 'criterion_id');
}
?>
<#66>
<?php
if ($ilDB->tableColumnExists('xlas_corrector_comment','points')) {
    $ilDB->dropTableColumn('xlas_corrector_comment', 'points');
}
?>
<#67>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_comment','parent_number')) {
    $ilDB->addTableColumn('xlas_corrector_comment', 'parent_number', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));}
?>
<#68>
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

	)
);
if (! $ilDB->tableExists('xlas_location')) {
	$ilDB->createTable('xlas_location', $fields);
	$ilDB->addPrimaryKey('xlas_location', array( 'id' ));
	$ilDB->addIndex("xlas_location", array("task_id"), "i1");

	if (! $ilDB->sequenceExists('xlas_location')) {
		$ilDB->createSequence('xlas_location');
	}
}
?>
<#69>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','location')) {
	$ilDB->addTableColumn('xlas_essay', 'location', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4',
	]);
	$ilDB->addIndex("xlas_essay", array("location"), "i4");
}
?>
<#70>
<?php
if (!$ilDB->tableColumnExists('xlas_task_settings','closing_message')) {
	$ilDB->addTableColumn('xlas_task_settings', 'closing_message', [
		'type' => 'clob'
	]);
}
?>
<#71>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','criteria_mode')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'criteria_mode', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',
        'default' => 'none'
    ]);
}
?>
<#72>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_comment','points')) {
    $ilDB->addTableColumn('xlas_corrector_comment', 'points', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ]);
}
?>
<#73>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector','criterion_copy')) {
	$ilDB->addTableColumn('xlas_corrector', 'criterion_copy', [
		'notnull' => '1',
		'type' => 'integer',
		'length' => '1',
	]);
}
?>
<#74>
<?php
if (!$ilDB->tableColumnExists('xlas_rating_crit','corrector_id')) {
	$ilDB->addTableColumn('xlas_rating_crit', 'corrector_id', [
		'notnull' => '0',
		'type' => 'integer',
		'length' => '4'
	]);
}
?>
<#75>
<?php
// Migrate all files of stakeholder xlas to xlas_resource
$set = $ilDB->query("SELECT file_id FROM xlas_resource WHERE file_id IS NOT NULL;");
$result = $ilDB->fetchAll($set);

if(count($result) > 0){
	$ilDB->manipulate("UPDATE il_resource_stkh_u SET stakeholder_id='xlas_resource' WHERE stakeholder_id LIKE 'xlas'");
}

$set = $ilDB->query("SELECT id FROM il_resource_stkh WHERE id LIKE 'xlas_resource';");
$result = $ilDB->fetchAll($set);
if(count($result) == 0){
	$ilDB->manipulate("UPDATE il_resource_stkh SET id='xlas_resource' WHERE id LIKE 'xlas'");
}else{
	$ilDB->manipulate("DELETE FROM il_resource_stkh WHERE id LIKE 'xlas'");
}

?>
<#76>
<?php
if ($ilDB->tableColumnExists('xlas_essay','pdf_version')) {
	$ilDB->modifyTableColumn('xlas_essay', 'pdf_version', [
		'notnull' => '0',
		'type' => 'text',
		'length' => '50',
        'default' => null
	]);
}
?>
<#77>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_comment','mark')) {
    $ilDB->addTableColumn('xlas_corrector_comment', 'mark', [
        'type' => 'text',
        'length' => '4000',
    ]);
}
?>
<#78>
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
    'page_no' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),
    'file_id' => array(
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',
    ),
    'width' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),
    'height' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
    ),

    
);
if (! $ilDB->tableExists('xlas_essay_image')) {
    $ilDB->createTable('xlas_essay_image', $fields);
    $ilDB->addPrimaryKey('xlas_essay_image', array( 'id' ));
    $ilDB->addIndex("xlas_essay_image", array("essay_id"), "i1");

    if (! $ilDB->sequenceExists('xlas_essay_image')) {
        $ilDB->createSequence('xlas_essay_image');
    }
}
?>
<#79>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_comment','marks')) {
    $ilDB->addTableColumn('xlas_corrector_comment', 'marks', [
        'type' => 'text',
        'length' => '4000',
    ]);
}
?>
<#80>
<?php
if (!$ilDB->tableColumnExists('xlas_essay_image', 'mime')) {
    $ilDB->addTableColumn('xlas_essay_image', 'mime', [
       'notnull' => '1',
       'type' => 'text',
       'length' => '255'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_essay_image', 'thumb_id')) {
    $ilDB->addTableColumn('xlas_essay_image', 'thumb_id', [
        'notnull' => '0',
        'type' => 'text',
        'length' => '50',
    ]);
}
if (!$ilDB->tableColumnExists('xlas_essay_image', 'thumb_width')) {
    $ilDB->addTableColumn('xlas_essay_image', 'thumb_width', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
    ]);
}
if (!$ilDB->tableColumnExists('xlas_essay_image', 'thumb_height')) {
    $ilDB->addTableColumn('xlas_essay_image', 'thumb_height', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
    ]);
}
if (!$ilDB->tableColumnExists('xlas_essay_image', 'thumb_mime')) {
    $ilDB->addTableColumn('xlas_essay_image', 'thumb_mime', [
        'notnull' => '0',
        'type' => 'text',
        'length' => '255'
    ]);
}

?>
<#81>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_summary', 'include_comments')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'include_comments', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corrector_summary', 'include_comment_ratings')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'include_comment_ratings', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corrector_summary', 'include_comment_points')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'include_comment_points', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corrector_summary', 'include_criteria_points')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'include_criteria_points', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
?>
<#82>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector_summary', 'include_writer_notes')) {
    $ilDB->addTableColumn('xlas_corrector_summary', 'include_writer_notes', [
        'notnull' => '0',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}

?>
<#83>
<?php
if (!$ilDB->tableExists('xlas_corrector_prefs')) {
    $ilDB->createTable('xlas_corrector_prefs', [
       'corrector_id' => [
           'notnull' => '1',
           'type' => 'integer',
           'length' => '4'
       ],
       'essay_page_zoom' => [
           'notnull' => '1',
           'type' => 'float',
       ],
       'essay_text_zoom' => [
           'notnull' => '1',
           'type' => 'float',
       ],
       'summary_text_zoom' => [
           'notnull' => '1',
           'type' => 'float',
       ],
       'include_comments' => [
           'notnull' => '1',
           'type' => 'integer',
           'length' => '4'
       ],
       'include_comment_ratings' => [
           'notnull' => '1',
           'type' => 'integer',
           'length' => '4'
       ],
       'include_comment_points' => [
           'notnull' => '1',
           'type' => 'integer',
           'length' => '4'
       ],
       'include_criteria_points' => [
           'notnull' => '1',
           'type' => 'integer',
           'length' => '4'
       ],
       'include_writer_notes' => [
           'notnull' => '1',
           'type' => 'integer',
           'length' => '4'
       ],
    ]);
    $ilDB->addPrimaryKey('xlas_corrector_prefs', ['corrector_id']);
}
?>
<#84>
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
    'note_no' => array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',

    ),
    'note_text' => array(
        'type' => 'clob',

    ),
    'last_change' => array(
        'type' => 'timestamp',
    )
);

// recreate the table with the new scheme (was not yet used)
if ($ilDB->tableExists('xlas_writer_notice')) {
    $ilDB->dropTable('xlas_writer_notice');
}
$ilDB->createTable('xlas_writer_notice', $fields);
$ilDB->addPrimaryKey('xlas_writer_notice', array( 'id' ));
$ilDB->addIndex("xlas_writer_notice", array("essay_id"), "i1");

if (! $ilDB->sequenceExists('xlas_writer_notice')) {
    $ilDB->createSequence('xlas_writer_notice');
}
?>
<#85>
<?php
if (!$ilDB->tableExists('xlas_writer_prefs')) {
    $ilDB->createTable('xlas_writer_prefs', [
        'writer_id' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4'
        ],
        'instructions_zoom' => [
            'notnull' => '1',
            'type' => 'float',
        ],
        'editor_zoom' => [
            'notnull' => '1',
            'type' => 'float',
        ],
    ]);
    $ilDB->addPrimaryKey('xlas_writer_prefs', ['writer_id']);
}
?>
<#86>
<?php
if ($ilDB->tableColumnExists('xlas_writer', 'editor_font_size')) {
    $ilDB->dropTableColumn('xlas_writer', 'editor_font_size');
}
?>
<#87>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'positive_rating')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'positive_rating', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',
        'default' => 'Exzellent'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'negative_rating')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'negative_rating', [
        'notnull' => '1',
        'type' => 'text',
        'length' => '50',
        'default' => 'Kardinal'
    ]);
}
?>
<#88>
<?php
if ($ilDB->tableColumnExists('xlas_essay', 'processed_text')) {
    $ilDB->dropTableColumn('xlas_essay', 'processed_text');
}

if (!$ilDB->tableColumnExists('xlas_essay', 'service_version')) {
    $ilDB->addTableColumn('xlas_essay', 'service_version', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
?>
<#89>
<?php
if (!$ilDB->tableColumnExists('xlas_editor_settings', 'add_paragraph_numbers')) {
    $ilDB->addTableColumn('xlas_editor_settings', 'add_paragraph_numbers', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '1'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_editor_settings', 'add_correction_margin')) {
    $ilDB->addTableColumn('xlas_editor_settings', 'add_correction_margin', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_editor_settings', 'left_correction_margin')) {
    $ilDB->addTableColumn('xlas_editor_settings', 'left_correction_margin', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_editor_settings', 'right_correction_margin')) {
    $ilDB->addTableColumn('xlas_editor_settings', 'right_correction_margin', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
?>
<#90>
<?php
if (!$ilDB->tableExists('xlas_pdf_settings')) {
    $ilDB->createTable('xlas_pdf_settings', [
        'task_id' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4'
        ],
        'add_header' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '1'
        ],
        'add_footer' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '1'
        ],
        'top_margin' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '10'
        ],
        'bottom_margin' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '10'
        ],
        'left_margin'=> [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '10'
        ],
        'right_margin' => [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '10'
        ]
    ]);
    $ilDB->addPrimaryKey('xlas_pdf_settings', ['task_id']);
}
?>
<#91>
<?php
    $ilDB->manipulate("UPDATE xlas_editor_settings SET headline_scheme='three' WHERE headline_scheme='none'");
?>
<#92>
<?php
    if (!$ilDB->tableColumnExists('xlas_editor_settings', 'allow_spellcheck')) {
        $ilDB->addTableColumn('xlas_editor_settings', 'allow_spellcheck', [
            'notnull' => '1',
            'type' => 'integer',
            'length' => '4',
            'default' => '0'
        ]);
}
?>
<#93>
<?php
    // cleanup wrong corrector assignments created from a wrong import
    // these are assignments across different tasks
    $query = "
        DELETE a
        FROM xlas_corrector_ass a
        LEFT JOIN xlas_writer w ON w.id = a.writer_id
        LEFT JOIN xlas_corrector c ON c.id = a.corrector_id
        WHERE c.id IS NULL OR w.id IS NULL OR c.task_id <> w.task_id
    ";
?>
<#94>
<?php
    // fix wrongly created pseudonyms
    $query = "
        UPDATE xlas_writer 
        SET pseudonym = CONCAT('Teilnehmer/in ', id)
        WHERE pseudonym = 'Teilnehmer/in 0'
    ";
?>
<#95>
<?php
if (!$ilDB->tableColumnExists('xlas_task_settings','review_enabled')) {
    $ilDB->addTableColumn('xlas_task_settings', 'review_enabled', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#96>
<?php
if (!$ilDB->tableColumnExists('xlas_task_settings','review_notification')) {
    $ilDB->addTableColumn('xlas_task_settings', 'review_notification', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#97>
<?php
if (!$ilDB->tableColumnExists('xlas_essay','review_notification')) {
    $ilDB->addTableColumn('xlas_essay', 'review_notification', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#98>
<?php
if (!$ilDB->tableColumnExists('xlas_task_settings','review_notif_text')) {
    $ilDB->addTableColumn('xlas_task_settings', 'review_notif_text', [
        'notnull' => '0',
        'type' => 'clob',
        'default' => null
    ]);
}
?>
<#99>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','anonymize_correctors')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'anonymize_correctors', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#100>
<?php
// remove wrongly created columns
if ($ilDB->tableColumnExists('xlas_essay','correction_authorized')) {
    $ilDB->dropTableColumn('xlas_essay','correction_authorized');
}
if ($ilDB->tableColumnExists('xlas_essay','correction_authorized_by')) {
    $ilDB->dropTableColumn('xlas_essay','correction_authorized_by');
}
?>
<#101>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'fixed_inclusions')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'fixed_inclusions', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '0'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'include_comments')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'include_comments', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '1'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'include_comment_ratings')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'include_comment_ratings', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '1'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'include_comment_points')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'include_comment_points', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '1'
    ]);
}
if (!$ilDB->tableColumnExists('xlas_corr_setting', 'include_criteria_points')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'include_criteria_points', [
        'notnull' => '1',
        'type' => 'integer',
        'length' => '4',
        'default' => '1'
    ]);
}
?>
<#102>
<?php
if (!$ilDB->tableColumnExists('xlas_corrector','correction_report')) {
    $ilDB->addTableColumn('xlas_corrector', 'correction_report', array(
        'type' => 'clob'
    ));
}
?>
<#103>
<?php
// enable review for existing tasks
// before adding the switch, the review was just controlled by the start and end date
// if no dates were configured, the review was possible
// new tasks will not activate the review by default
$ilDB->manipulate("UPDATE xlas_task_settings SET review_enabled = 1");
?>
<#104>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','reports_enabled')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'reports_enabled', array(
        'notnull' => '1',
        'type' => 'integer',
        'length' => 4,
        'default' => 0
    ));
}
?>
<#105>
<?php
if (!$ilDB->tableColumnExists('xlas_corr_setting','reports_available_start')) {
    $ilDB->addTableColumn('xlas_corr_setting', 'reports_available_start', array(
        'type' => 'timestamp'
    ));
}
?>
