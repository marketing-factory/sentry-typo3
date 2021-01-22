<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Jean85\PrettyVersions;
use PackageVersions\Versions;
use Sentry\Event;
use TYPO3\CMS\Core\Core\Environment;

class Typo3Context implements ContextInterface
{
    public function appliesToEvent(Event $event): bool
    {
        return true;
    }

    public function addToEvent(Event $event): void
    {
        $event->setTags(array_merge_recursive($event->getTags(), [
            'typo3_version' => TYPO3_version,
            'typo3_mode' => TYPO3_MODE,
            'application_context' => (string)Environment::getContext(),
            'application_version' => PrettyVersions::getVersion(Versions::ROOT_PACKAGE_NAME)->getPrettyVersion(),
        ]));
    }
}
