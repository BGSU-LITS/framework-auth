<?php

declare(strict_types=1);

namespace Lits\Action;

use InvalidArgumentException;
use Jasny\Auth\Auth;
use Lits\Action;
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

    /**
     * @param array<string, string> $data
     * @throws HttpInternalServerErrorException
     */
    protected function setup(
        ServerRequest $request,
        Response $response,
        array $data
    ): void {
        parent::setup($request, $response, $data);

        try {
            $this->response = $this->response->withHeader(
                'Cache-Control',
                'no-store'
            );
        } catch (InvalidArgumentException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception
            );
        }
    }
}
