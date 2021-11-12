<?php

namespace ILIAS\Plugin\LongEssayTask\Data;

/**
 * @author Fred Neumann <neumann@ilias.de>
 */
class AccessTokenDatabaseRepository
{
    /**
     * @param int $a_user_id
     * @param int $a_task_id
     * @return AccessToken
     */
    public function createToken(int $a_user_id, int $a_task_id): AccessToken
    {
        $token = new AccessToken();
        $token->setUserId($a_user_id);
        $token->setTaskId($a_task_id);
        return $token;
    }

    /**
     * @param int $a_id
     * @return AccessToken|null
     */
    public function getTokenById(int $a_id): ?AccessToken
    {
        $token = AccessToken::findOrGetInstance($a_id);
        if ($token != null) {
            return $token;
        }
        return null;
    }


    /**
     * Get a token by its values
     * @param int $a_user_id
     * @param int $a_task_id
     * @return AccessToken|null
     */
    public function getTokenByUserAndTask(int $a_user_id, int $a_task_id): ?AccessToken
    {
        /** @var AccessToken $token */
        $token = AccessToken::where(['user_id'=> $a_user_id, 'task_id' => $a_task_id])->first();
        if ($token != null) {
            return $token;
        }
        return null;
    }


    /**
     * Save a token
     * @param AccessToken $a_token
     */
    public function saveToken(AccessToken $a_token)
    {
        $a_token->save();
    }


    /**
     * Delete a token
     * @param int $a_id
     */
    public function deleteToken(int $a_id)
    {
        $token = $this->getTokenById($a_id);

        if ( $token != null ){
            $token->delete();
        }
    }

    /**
     * Delete all tokens that are no longer valid
     */
    public function deleteInvalidTokens()
    {
        try {
            $valid = new \ilDateTime(time(), IL_CAL_UNIX);
        }
        catch (\ilDateTimeException $e) {
            $valid = '';
        }
        $tokens = AccessToken::where(['valid_until' < $valid])->get();
        foreach ($tokens as $token) {
            $token->delete();
        }
    }
}

