<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\Data;

use ilDBInterface;
use ILIAS\Plugin\LongEssayAssessment\System\Data\UserData;
use ilUserQuery;
use PHPUnit\Exception;
use DateTimeZone;
use ilObjUser;
use ilLanguage;
use ILIAS\Plugin\LongEssayAssessment\Common\RecordRepo\HydrationInterface;

class UserDataRepo implements \Edutiek\AssessmentService\System\Data\UserDataRepo, HydrationInterface
{
    /**
     * @var UserData
     */
    private array $dehydrated = [];

    public function __construct(
        private ilDBInterface $db,
        private ilLanguage $lng,
        private ilObjUser $user,
        private ilUserQuery $user_query
    ) {
    }

    public function one(int $id): ?UserData
    {
        foreach ($this->queryUsers([$id]) as $user) {
            return $user;
        }
        return null;
    }

    public function some(array $ids): array
    {
        return $this->queryUsers($ids);
    }

    public function current(): ?UserData
    {
        return (new UserData(
            $this->user->getId(),
        ))->setValues(
            $this->user->getLogin(),
            empty($this->user->getTitle()) ? null : $this->user->getTitle(),
            $this->user->getFirstname(),
            $this->user->getLastname(),
            $this->user->getLanguage(),
            new DateTimeZone($this->user->getTimeZone())
        );
    }

    public function idByLogin(string $login): int
    {
        return ilObjUser::getUserIdByLogin($login);
    }

    /**
     * @param int[] $ids
     * @return UserData[] indexed by usr_id
     */
    private function queryUsers(array $ids): array
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
                    $languages[$row['usr_id']] = $row['value'];
                    break;
                case 'usr_tz':
                    try {
                        $timezones[$row['usr_id']] = new DateTimeZone($row['value']);
                    } catch (Exception) {
                    }
                    break;
            }
        }

        $query = clone $this->user_query;
        $query->setLimit(99999);
        $query->setOffset(0);
        $query->setOrderField('lastname');
        $query->setOrderDirection('asc');
        $query->setUserFilter($ids);
        $query->setAdditionalFields(['matriculation']);

        $result = $query->query();
        foreach ($result['set'] ?? [] as $row) {
            $users[(int) $row['usr_id']] = (new UserData(
                (int) $row['usr_id']
            ))->setValues(
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

    public function dehydratedInstance(mixed $key_value): ?object
    {
        if ($key_value === null) {
            return null;
        }

        return $this->dehydrated[$key_value] ??= new UserData($key_value);
    }

    public function hydrate(): void
    {
        $default_language = $this->lng->getDefaultLanguage();
        $default_timezone = new DateTimeZone(date_default_timezone_get());

        $users = [];
        $languages = [];
        $timezones = [];

        $pref_query = "SELECT usr_id, keyword, `value` FROM usr_pref where (keyword = 'language' OR keyword = 'user_tz')"
            . ' AND ' . $this->db->in('usr_id', array_keys($this->dehydrated), false, 'integer');
        $result = $this->db->query($pref_query);
        while ($row = $this->db->fetchAssoc($result)) {
            switch ($row['keyword']) {
                case 'language':
                    $languages[$row['usr_id']] = $row['value'];
                    break;
                case 'usr_tz':
                    try {
                        $timezones[$row['usr_id']] = new DateTimeZone($row['value']);
                    } catch (Exception) {
                    }
                    break;
            }
        }

        $query = clone $this->user_query;
        $query->setLimit(99999);
        $query->setOffset(0);
        $query->setOrderField('lastname');
        $query->setOrderDirection('asc');
        $query->setUserFilter(array_keys($this->dehydrated));
        $query->setAdditionalFields(['matriculation']);

        $result = $query->query();
        foreach ($result['set'] ?? [] as $row) {
            ($this->dehydrated[$row['usr_id']]?? null)?->setValues(
                (string) $row['login'],
                !empty($row['title']) ? (string) $row['title'] : null,
                (string) $row['lastname'] ?? '',
                (string) $row['firstname'] ?? '',
                $languages[$row['usr_id']] ?? $default_language,
                $timezones[$row['usr_id']] ?? $default_timezone
            );
            unset($this->dehydrated[$row['usr_id']]);
        }
    }

}
