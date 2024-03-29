<?php

declare(strict_types=1);

namespace Lits\Connector;

use Lits\Config\LdapConfig;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;

final class LdapConnector
{
    public function __construct(protected Settings $settings)
    {
    }

    /** @throws InvalidConfigException */
    public function domain(): string
    {
        \assert($this->settings['ldap'] instanceof LdapConfig);

        if (!$this->settings['ldap']->enabled) {
            throw new InvalidConfigException(
                'LDAP is not enabled and cannot provide domain',
            );
        }

        $this->settings['ldap']->testDomain();

        return $this->settings['ldap']->domain;
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    public function verify(string $username, string $password): bool
    {
        \assert($this->settings['ldap'] instanceof LdapConfig);

        if (!$this->settings['ldap']->enabled) {
            return false;
        }

        $this->settings['ldap']->testDomain();

        $parts = \explode('@', $username, 2);

        if (\count($parts) !== 2) {
            throw new InvalidDataException('Invalid email address');
        }

        [$username, $domain] = $parts;

        if ($domain !== $this->settings['ldap']->domain) {
            return false;
        }

        return $this->bind($this->uri(), $username, $password);
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    private function bind(
        string $uri,
        string $username,
        string $password,
    ): bool {
        \assert($this->settings['ldap'] instanceof LdapConfig);

        $ldap = \ldap_connect($uri);

        if ($ldap === false) {
            throw new InvalidDataException('Could not set up LDAP connection');
        }

        if ($this->settings['ldap']->start_tls) {
            if (!\ldap_start_tls($ldap)) {
                throw new InvalidDataException(
                    'Could not start TLS for LDAP connection',
                );
            }
        }

        $this->settings['ldap']->testBind();

        // phpcs:ignore
        $result = ldap_bind_ext(
            $ldap,
            \sprintf($this->settings['ldap']->bind, $username),
            $password,
        );

        return $result !== false;
    }

    /** @throws InvalidConfigException */
    private function uri(): string
    {
        \assert($this->settings['ldap'] instanceof LdapConfig);

        $this->settings['ldap']->testHost();

        $uri = 'ldap://' . $this->settings['ldap']->host;

        if (\is_int($this->settings['ldap']->port)) {
            $uri .= ':' . (string) $this->settings['ldap']->port;
        }

        return $uri;
    }
}
