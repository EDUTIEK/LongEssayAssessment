<?php
/* Copyright (c) 2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\LongEssayTask\Data;


/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class CriterionPoints extends ActivePluginRecord
{

    /**
     * @var string
     */
    protected $connector_container_name = 'xlet_crit_points';


	/**
	 * ID
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       true
	 * @con_sequence         true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $id;

	/**
	 * The object id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $rating_id;

	/**
	 * The corrector comment id
	 *
	 * @var integer
	 * @con_has_field        true
	 * @con_is_primary       false
	 * @con_sequence         false
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $corr_comment_id;

	/**
	 * @var int
	 * @con_has_field        true
	 * @con_is_notnull       true
	 * @con_fieldtype        integer
	 * @con_length           4
	 */
	protected int $points = 0;
}