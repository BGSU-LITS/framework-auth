<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;
use Lits\Exception\InvalidConfigException;

final class AuthConfig extends Config
{
    public ?string $context = null;
    public ?DatabaseConfig $database = null;
    public int $expires = 15 * 60;
    public ?string $required = null;
    public ?string $url = null;

    /** @throws InvalidConfigException */
    public function urlLogin(?string $return = null): string
    {
        if (\is_null($this->url)) {
            throw new InvalidConfigException('The base URL must be specified');
        }

        $login = \rtrim($this->url, '/') . '/login';

        if (\is_null($return)) {
            return $login;
        }

        return $login . '?return=' . \urlencode($return);
    }
}
