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
    public function __construct(private Settings $settings)
    {
    }

    private function requiredFromRequest(ServerRequest $request): ?string
    {
        \assert($this->settings['auth'] instanceof AuthConfig);

        $route = RouteContext::fromRequest($request)->getRoute();

        if ($route instanceof Route) {
            $argument = $route->getArgument('auth');

            if (\is_string($argument)) {
                return $argument;
            }
        }

        return $this->settings['auth']->required;
    }

    public function __invoke(ServerRequest $request): string|bool|null
    {
        $required = $this->requiredFromRequest($request);

        if (!\is_string($required) || $required === 'null') {
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
