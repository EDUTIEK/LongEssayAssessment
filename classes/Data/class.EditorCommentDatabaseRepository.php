<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fabian Wolf <wolf@ilias.de>
 */
class EditorCommentDatabaseRepository implements EditorCommentRepository
{

	public function createEditorComment(EditorComment $a_comment)
	{
		$a_comment->create();
	}

	public function getEditorCommentById(int $a_id): ?EditorComment
	{
		$comment =  EditorComment::findOrGetInstance($a_id);
		if ($comment != null) {
			return $comment;
		}
		return null;
	}

	public function ifEditorCommentExistsById(int $a_id): bool
	{
		return ( $this->getEditorCommentById($a_id) != null );
	}

	public function updateEditorComment(EditorComment $a_comment)
	{
		$a_comment->update();
	}

	public function deleteEditorComment(int $a_id)
	{
		$comment = $this->getEditorCommentById($a_id);

		if ( $comment != null ){
			$comment->delete();
		}
	}
}

