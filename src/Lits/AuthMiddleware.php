<?php

declare(strict_types=1);

namespace Lits;

use Jasny\Auth\Auth;
use Jasny\Auth\AuthMiddleware as JasnyAuthMiddleware;
use Jasny\Auth\ContextInterface as Context;
use Lits\Config\AuthConfig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;

final class AuthMiddleware extends JasnyAuthMiddleware
{
    private ?AuthConfig $config = null;
    private ?Context $context = null;

    public function withConfig(?AuthConfig $config = null): self
    {
        $copy = clone $this;
        $copy->config = $config;

        return $copy;
    }

    public function withContext(?Context $context = null): self
    {
        $copy = clone $this;
        $copy->context = $context;

        return $copy;
    }

    protected function initialize(ServerRequest $request): void
    {
        parent::initialize($request);

        if (
            $this->auth instanceof Auth &&
            $this->context instanceof Context
        ) {
            $this->auth->setContext($this->context);
        }
    }

    /**
     * @throws HttpForbiddenException
     * @throws HttpUnauthorizedException
     */
    protected function forbidden(
        ServerRequest $request,
        ?Response $response = null
    ): Response {
        if ($this->auth->isLoggedIn()) {
            throw new HttpForbiddenException($request);
        }

        return $this->redirect($request, $response);
    }

    /** @throws HttpUnauthorizedException */
    private function redirect(
        ServerRequest $request,
        ?Response $response
    ): Response {
        if (
            $this->config instanceof AuthConfig &&
            !\is_null($this->config->url) &&
            $this->config->url !== '' &&
            $request->getMethod() === 'GET'
        ) {
            try {
                return $this->createResponse(302, $response)->withHeader(
                    'Location',
                    \rtrim($this->config->url, '/') . '/login?return=' .
                    \urlencode(
                        (string) $request->getUri()
                            ->withScheme('')
                            ->withHost('')
                            ->withPort(null)
                    )
                );
            } catch (\InvalidArgumentException $exception) {
                throw new HttpUnauthorizedException(
                    $request,
                    null,
                    $exception
                );
            }
        }

        throw new HttpUnauthorizedException($request);
    }
}
