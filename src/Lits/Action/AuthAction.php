<?php

declare(strict_types=1);

namespace Lits\Action;

use InvalidArgumentException;
use Jasny\Auth\Auth;
use Lits\Action;
use Lits\Config\AuthConfig;
use Lits\Service\AuthActionService;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

abstract class AuthAction extends Action
{
    protected Auth $auth;

    public function __construct(AuthActionService $service)
    {
        parent::__construct($service->service);

        $this->auth = $service->auth;

        $this->template->global('auth', $this->auth);
    }

    /** @throws HttpInternalServerErrorException */
    protected function redirectLogin(
        ?string $return = null,
        ?int $status = null,
    ): void {
        \assert($this->settings['auth'] instanceof AuthConfig);

        try {
            $url = $this->settings['auth']->urlLogin(
                $return ?? (string) $this->request->getUri()
                    ->withScheme('')
                    ->withHost('')
                    ->withPort(null),
            );
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }

        $this->redirect($url, $status);
    }

    /**
     * @param array<string, string> $data
     * @throws HttpInternalServerErrorException
     */
    #[\Override]
    protected function setup(
        ServerRequest $request,
        Response $response,
        array $data,
    ): void {
        parent::setup($request, $response, $data);

        try {
            $this->response = $this->response->withHeader(
                'Cache-Control',
                'no-store',
            );
        } catch (InvalidArgumentException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }
}
