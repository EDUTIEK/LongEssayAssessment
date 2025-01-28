<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserData as UserModel;
use Edutiek\AssessmentService\System\Data\UserData;
use ilUserQuery;
use PHPUnit\Exception;
use DateTimeZone;
use ilObjUser;
use ilLanguage;
use ilUserUtil;

class UserRepo implements \Edutiek\AssessmentService\System\Data\UserRepo
{
    public function __construct(
        private ilDBInterface $db,
        private readonly ilLanguage $lng,
        private ilObjUser $user,
        private ilUserQuery $user_query,
        private ilUserUtil $user_util
    ) {
    }

    public function getUser(int $id): ?UserData
    {
        foreach ($this->queryUsers([$id]) as $user) {
            return $user;
        }
        return null;
    }

    public function getUsersByIds(array $ids): array
    {
        return $this->queryUsers($ids);
    }

    public function getCurrentUser(): ?UserData
    {
        return new UserModel(
            $this->user->getId(),
            $this->user->getLogin(),
            empty($this->user->getTitle()) ? null : $this->user->getTitle(),
            $this->user->getFirstname(),
            $this->user->getLastname(),
            $this->user->getLanguage(),
            new DateTimeZone($this->user->getTimeZone())
        );
    }

    public function getUserDisplay(int $id, ?string $back_link): UserDisplay
    {
        $result = $this->user_util::getNamePresentation(
            $id,
            true,
            true,
            (string) $back_link,
            false,
            false,
            false,
            true
        );

        return new UserDisplay(
            $id,
            $data['img'] ?? null,
            $data['link'] ?? null
        );
    }

    public function getUserDisplaysByIds(array $ids, ?string $back_link): array
    {
        $result = $this->user_util::getNamePresentation(
            $ids,
            true,
            true,
            (string) $back_link,
            false,
            false,
            false,
            true
        );

        $displays = [];
        foreach ($ids as $id) {
            $displays[$id] = new UserDisplay(
                $id,
                $data[$id]['img'] ?? null,
                $data[$id]['link'] ?? null
            );
        }

        return $displays;
    }

    /**
     * @param int[] $ids
     * @return UserData[] indexed by usr_id
     */
    public function queryUsers(array $ids): array
    {
        $default_language = $this->lng->getDefaultLanguage();
        $default_timezone = new DateTimeZone(date_default_timezone_get());

        $users = [];
        $languages = [];
        $timezones = [];

        $pref_query = "SELECT usr_id, keyword, `value` FROM usr_pref where (keyword = 'language' OR keyword = 'user_tz')"
            . ' AND ' . $this->db->in('usr_id', $ids, false, 'integer');
        $result = $this->db->query($pref_query);
        while ($row = $this->db->fetchAssoc($result)) {
            switch ($row['keyword']) {
                case 'language':
                    $this->languages[$row['usr_id']] = $row['value'];
                    break;
                case 'usr_tz':
                    try {
                        $this->timezones[$row['usr_id']] = new DateTimeZone($row['uset_tz']);
                    } catch (Exception) {
                    }
                    break;
            }
        }

        $query = clone $this->user_query;
        $query->setLimit(0);
        $query->setOffset(0);
        $query->setOrderField('lastname');
        $query->setOrderDirection('asc');
        $query->setUserFilter($ids);
        $query->setAdditionalFields(['matriculation']);

        $result = $query->query();
        foreach ($result['set'] ?? [] as $row) {
            $users = new UserModel(
                (int) $row['usr_id'],
                (string) $row['login'],
                !empty($row['title']) ? (string) $row['title'] : null,
                (string) $row['lastname'] ?? '',
                (string) $row['firstname'] ?? '',
                $languages[$row['usr_id']] ?? $default_language,
                $timezones[$row['usr_id']] ?? $default_timezone
            );
        }

        return $users;
    }
}
