<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3\Integration;

use Sentry\Event;
use Symfony\Component\Console\Input\ArgvInput;
use TYPO3\CMS\Core\Core\Environment;

class CommandContext implements ContextInterface
{
    public function appliesToEvent(Event $event): bool
    {
        return Environment::isCli();
    }

    public function addToEvent(Event $event): void
    {
        $input = new ArgvInput();
        $event->setExtra(array_merge_recursive($event->getExtra(), [
            'typo3.command' => $input->getFirstArgument() ?? 'list',
        ]));

        $event->setTags(array_merge_recursive($event->getTags(), [
            'typo3.command' => $input->getFirstArgument() ?? 'list',
        ]));
    }
}
