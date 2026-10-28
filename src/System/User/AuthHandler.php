<?php

declare(strict_types=1);

namespace ILIAS\Plugin\LongEssayAssessment\System\User;

use ilObjUser;
use ilObjectFactory;
use ILIAS\Authentication\Password\LocalUserPasswordManager;
use ILIAS\DI\Container;
use ILIAS\LegalDocuments\ConsumerToolbox\Blocks;
use ILIAS\LegalDocuments\ConsumerToolbox\User as LegalDocumentsUser;
use ILIAS\TermsOfService\Consumer as TermsOfService;
use ILIAS\TermsOfService\Settings as TermsOfServiceSettings;
use ILIAS\TermsOfService\UserSettings as TermsOfServiceUserSettings;
use ilSecuritySettings;
use ilAuthUtils;
use ilLogger;

/**
 * Authentication handler needed by the asssment service
 * Currently only a local authentication is supported
 */
readonly class AuthHandler implements \Edutiek\AssessmentService\System\User\AuthHandler
{
    public function __construct(
        private ilLogger $logger,
        private Container $dic,
    ) {
    }

    /**
     * @see ilAuthProviderDatabase
     */
    public function checkPassword(int $user_id, string $password): bool
    {
        if ($this->isPasswordEnabled($user_id)) {
            $user = new ilObjUser($user_id);
            return LocalUserPasswordManager::getInstance()->verifyPassword($user, $password);
        }

        return false;
    }

    /**
     * @see ilAuthFrontend::handleLoginAttempts
     */
    public function handleFailedLogin(int $user_id): void
    {
        $security = ilSecuritySettings::_getInstance();
        $max_attempts = $security->getLoginMaxAttempts();
        if ($max_attempts < 1) {
            return;
        }

        if ($user_id > 0 && $this->isPasswordEnabled($user_id)) {
            $num_login_attempts = ilObjUser::_getLoginAttempts($user_id);
            $login = ilObjUser::_lookupLogin($user_id);

            if ($num_login_attempts <= $max_attempts) {
                ilObjUser::_incrementLoginAttempts($user_id);
                $this->logger->notice(sprintf(
                    'Incremented login attempts for user %s with id %s.',
                    $login,
                    $user_id
                ));
            }

            if ($num_login_attempts >= $max_attempts) {
                ilObjUser::_setUserInactive($user_id);
                $this->logger->warning(sprintf(
                    'User account %s with id %s set to inactive due to exceeded login attempts.',
                    $login,
                    $user_id
                ));
            }
        }
    }

    public function isPasswordEnabled(int $user_id): bool
    {
        return ilAuthUtils::isLocalPasswordEnabledForAuthMode(
            (int) ilAuthUtils::_getAuthMode(ilObjUser::_lookupAuthMode($user_id))
        );
    }

    public function isActive(int $user_id): bool
    {
        return ilObjUser::_lookupActive($user_id);
    }

    /**
     * HTML of the terms-of-service document that applies to the user.
     * Empty if terms of service are disabled or no document matches.
     *
     * @see \ILIAS\LegalDocuments\ConsumerToolbox\User::matchingDocument()
     */
    public function getAgreement($user_id): string
    {
        $user = $this->termsOfServiceUser((int) $user_id);
        if ($user === null) {
            return '';
        }

        $document = $user->matchingDocument();
        if (!$document->isOk()) {
            return '';
        }

        return $document->value()->content()->value();
    }

    /**
     * @see \ILIAS\LegalDocuments\ConsumerToolbox\ConsumerSlots\PublicApi::needsToAgree()
     */
    public function hasAcceptedAgreement($user_id): bool
    {
        $user = $this->termsOfServiceUser((int) $user_id);
        if ($user === null || $user->cannotAgree()) {
            return true;
        }

        return !$user->needsToAcceptNewDocument();
    }

    /**
     * @see \ILIAS\LegalDocuments\ConsumerToolbox\User::acceptMatchingDocument()
     */
    public function acceptAgreement($user_id): void
    {
        $user = $this->termsOfServiceUser((int) $user_id);
        if ($user === null || $user->cannotAgree() || !$user->matchingDocument()->isOk()) {
            return;
        }

        $user->acceptMatchingDocument();
    }

    /**
     * Terms-of-service state for a user, or null if terms of service are disabled.
     */
    private function termsOfServiceUser(int $user_id): ?LegalDocumentsUser
    {
        $provide = $this->dic['legalDocuments']->provide(TermsOfService::ID);
        $blocks = new Blocks(TermsOfService::ID, $this->dic, $provide);
        $settings = new TermsOfServiceSettings(
            $blocks->selectSettingsFrom($blocks->readOnlyStore($blocks->globalStore()))
        );
        if (!$settings->enabled()->value()) {
            return null;
        }

        $user = new ilObjUser($user_id);

        return $blocks->user(
            $settings,
            new TermsOfServiceUserSettings(
                $user,
                $blocks->selectSettingsFrom($blocks->userStore($user)),
                $this->dic->refinery()
            ),
            $user
        );
    }
}
