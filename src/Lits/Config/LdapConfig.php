<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;
use Lits\Exception\InvalidConfigException;

final class LdapConfig extends Config
{
    public bool $enabled = false;
    public string $domain = '';
    public string $host = '';
    public ?int $port = null;
    public string $bind = '%s';
    public bool $start_tls = false;

    /** @throws InvalidConfigException */
    public function testDomain(): void
    {
        if ($this->domain === '') {
            throw new InvalidConfigException(
                'The LDAP domain must be specified',
            );
        }

        if (!self::validateHostname($this->domain)) {
            throw new InvalidConfigException(
                'The LDAP domain must be valid',
            );
        }
    }

    /** @throws InvalidConfigException */
    public function testHost(): void
    {
        if ($this->host === '') {
            throw new InvalidConfigException(
                'The LDAP host must be specified',
            );
        }

        if (!self::validateHostname($this->host)) {
            throw new InvalidConfigException(
                'The LDAP host must be valid',
            );
        }
    }

    /** @throws InvalidConfigException */
    public function testBind(): void
    {
        if ($this->bind === '') {
            throw new InvalidConfigException(
                'The LDAP bind must be specified',
            );
        }

        if (\strpos($this->bind, '%s') === false) {
            throw new InvalidConfigException(
                'The LDAP bind must have a placeholder',
            );
        }
    }

    private static function validateHostname(string $hostname): bool
    {
        return (bool) \filter_var(
            $hostname,
            \FILTER_VALIDATE_DOMAIN,
            \FILTER_FLAG_HOSTNAME,
        );
    }
}
