<?php

declare(strict_types=1);
namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use Sentry\UserDataBag;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserContext implements ContextInterface
{
    public function appliesToEvent(Event $event): bool
    {
        return !Environment::isCli();
    }

    public function addToEvent(Event $event): void
    {
        $user = [
            'ip_address' => GeneralUtility::getIndpEnv('REMOTE_ADDR'),
        ];
        $userType = TYPO3_MODE === 'FE' ? 'frontend' : 'backend';
        /** @var UserAspect $userAspect */
        $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect($userType . '.user');
        if ($userAspect->isLoggedIn()) {
            $user['userid'] = $userAspect->get('id');
            $user['username'] = $userAspect->get('username');
            $user['groups'] = implode(', ', $userAspect->getGroupNames());
        }

        $userDataBag = UserDataBag::createFromArray($user);
        $event->setUser($event->getUser() ? $event->getUser()->merge($userDataBag) : $userDataBag);
    }
}
