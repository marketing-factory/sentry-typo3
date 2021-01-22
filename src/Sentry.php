<?php

declare(strict_types=1);

namespace Helhum\SentryTypo3;

use Helhum\SentryTypo3\Integration\BeforeEventListener;
use Http\Discovery\Psr17FactoryDiscovery;
use Jean85\PrettyVersions;
use PackageVersions\Versions;
use Sentry\ClientBuilder;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\Integration\FatalErrorListenerIntegration;
use Sentry\SentrySdk;
use Sentry\Transport\DefaultTransportFactory;
use Symfony\Component\HttpClient\HttpClient;

final class Sentry
{
    /**
     * @var bool
     */
    private static $initialized = false;

    public static function initializeOnce(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;
        $defaultOptions = [
            'dsn' => null,
            'in_app_include' => [
                getenv('TYPO3_PATH_APP')
            ],
            'in_app_exclude' => [
                getenv('TYPO3_PATH_APP') . '/private',
                getenv('TYPO3_PATH_APP') . '/public',
                getenv('TYPO3_PATH_APP') . '/var',
                getenv('TYPO3_PATH_APP') . '/vendor',
            ],
            'prefixes' => [
                getenv('TYPO3_PATH_APP'),
            ],
            'environment' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['environment'] ?? 'production',
            'release' => PrettyVersions::getVersion(Versions::ROOT_PACKAGE_NAME)->getShortCommitHash(),
            'default_integrations' => false,
            'integrations' => [
                new FatalErrorListenerIntegration(),
            ],
            'before_send' => [BeforeEventListener::class, 'onBeforeSend'],
            'send_default_pii' => false,
            'error_types' => E_ALL & ~(E_STRICT | E_NOTICE | E_DEPRECATED | E_USER_DEPRECATED),
        ];
        $options = array_replace($defaultOptions, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sentry'] ?? []);
        unset($options['typo3_integrations']);
        $defaultOptions = [];
        $defaultOptions['verify_peer'] = filter_var(
            $GLOBALS['TYPO3_CONF_VARS']['HTTP']['verify'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? $GLOBALS['TYPO3_CONF_VARS']['verify'];
        $typo3HttpClient = HttpClient::create($defaultOptions);
        $clientBuilder = ClientBuilder::create($options);
        $uriFactory = Psr17FactoryDiscovery::findUriFactory();
        $requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

        $clientBuilder->setTransportFactory(new DefaultTransportFactory(
            $streamFactory,
            $requestFactory,
            new HttpClientFactory(
                $uriFactory,
                $responseFactory,
                $streamFactory,
                null,
                'sentry.php.typo3',
                PrettyVersions::getVersion('sentry/sentry')->getPrettyVersion()
            )
        ));
        SentrySdk::getCurrentHub()->bindClient($clientBuilder->getClient());
    }
}
