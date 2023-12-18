<?php

declare(strict_types=1);

use Jasny\Auth\Auth;
use Jasny\Auth\Authz\Groups;
use Jasny\Auth\AuthzInterface as Authz;
use Jasny\Auth\Confirmation\ConfirmationInterface as Confirmation;
use Jasny\Auth\Confirmation\TokenConfirmation;
use Jasny\Auth\ContextInterface as Context;
use Jasny\Auth\Session\Jwt;
use Jasny\Auth\Session\Jwt\CookieInterface as Cookie;
use Jasny\Auth\Session\Jwt\CookieMiddleware;
use Jasny\Auth\StorageInterface as Storage;
use Lits\AuthMiddleware;
use Lits\AuthRequired;
use Lits\AuthRequired\RouteAuthRequired;
use Lits\Config\AuthConfig;
use Lits\Config\DatabaseConfig;
use Lits\Data\AuthData;
use Lits\Database;
use Lits\Framework;
use Lits\Settings;
use Middlewares\Https as HttpsMiddleware;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface as Dispatcher;

use function DI\autowire;
use function DI\create;
use function DI\get;

// phpcs:ignore SlevomatCodingStandard.Complexity.Cognitive
return function (Framework $framework): void {
    $framework->addDefinition(
        Auth::class,
        autowire()
            ->constructorParameter('confirmation', get(Confirmation::class))
            ->property('dispatcher', get(Dispatcher::class)),
    );

    $framework->addDefinition(
        AuthMiddleware::class,
        function (
            Settings $settings,
            Auth $auth,
            AuthRequired $required,
            ResponseFactory $factory,
            ?Context $context,
            Jwt $jwt,
        ): AuthMiddleware {
            assert($settings['auth'] instanceof AuthConfig);

            $middleware = new AuthMiddleware($auth, $required, $factory);
            $ttl = $settings['auth']->expires;

            return $middleware
                ->withConfig($settings['auth'])
                ->withContext($context)
                ->withSession(
                    function (ServerRequest $request) use ($jwt, $ttl): Jwt {
                        $cookie = $request->getAttribute('jwt_cookie');
                        assert($cookie instanceof Cookie || $cookie === null);

                        if ($cookie instanceof Cookie) {
                            $jwt = $jwt->withCookie($cookie);
                        }

                        return $jwt->withTtl($ttl);
                    },
                );
        },
    );

    $framework->addDefinition(
        AuthRequired::class,
        get(RouteAuthRequired::class),
    );

    $framework->addDefinition(
        Authz::class,
        create(Groups::class)->constructor([
            'user' => [],
            'admin' => ['user'],
            'super' => ['admin'],
        ]),
    );

    $framework->addDefinition(
        Confirmation::class,
        get(TokenConfirmation::class),
    );

    $framework->addDefinition(
        Context::class,
        function (Settings $settings, Storage $storage): ?Context {
            assert($settings['auth'] instanceof AuthConfig);

            if (is_string($settings['auth']->context)) {
                return $storage->fetchContext($settings['auth']->context);
            }

            return null;
        },
    );

    $framework->addDefinition(
        CookieMiddleware::class,
        create()->constructor(
            '__Host-lits-auth',
            [
                'httponly' => true,
                'path' => '/',
                'samesite' => 'Lax',
                'secure' => true,
            ],
        ),
    );

    $framework->addDefinition(
        HttpsMiddleware::class,
        create()
            ->constructor(get(ResponseFactory::class))
            ->method('maxAge', 0),
    );

    $framework->addDefinition(
        Storage::class,
        function (Settings $settings): Storage {
            assert($settings['auth'] instanceof AuthConfig);

            if ($settings['auth']->database instanceof DatabaseConfig) {
                return new AuthData(
                    $settings,
                    new Database($settings['auth']->database),
                );
            }

            assert($settings['database'] instanceof DatabaseConfig);

            return new AuthData(
                $settings,
                new Database($settings['database']),
            );
        },
    );
};
