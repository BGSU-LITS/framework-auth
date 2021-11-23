<?php

declare(strict_types=1);

namespace Lits\AuthRequired;

use Lits\AuthRequired;
use Lits\Config\AuthConfig;
use Lits\Settings;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Interfaces\RouteInterface as Route;
use Slim\Routing\RouteContext;

final class RouteAuthRequired implements AuthRequired
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /** @return string|bool|null */
    public function __invoke(ServerRequest $request)
    {
        \assert($this->settings['auth'] instanceof AuthConfig);

        $required = $this->settings['auth']->required;
        $route = RouteContext::fromRequest($request)->getRoute();

        if ($route instanceof Route) {
            $argument = $route->getArgument('auth');

            if (\is_string($argument)) {
                $required = $argument;
            }
        }

        if (!\is_string($required)) {
            return null;
        }

        if ($required === 'true') {
            return true;
        }

        if ($required === 'false') {
            return false;
        }

        return $required;
    }
}
