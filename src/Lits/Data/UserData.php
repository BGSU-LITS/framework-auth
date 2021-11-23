<?php

declare(strict_types=1);

namespace Lits\Data;

use DateTimeInterface as DateTime;
use Jasny\Auth\ContextInterface as Context;
use Jasny\Auth\UserInterface as User;
use Lits\Config\LdapConfig;
use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use Safe\DateTimeImmutable;
use Safe\Exceptions\LdapException;
use Safe\Exceptions\PasswordException;
use Safe\Exceptions\StringsException;
use Throwable;

use function Latitude\QueryBuilder\field;
use function Safe\ldap_bind;
use function Safe\password_hash;
use function Safe\sprintf;

final class UserData extends DatabaseData implements User
{
    public ?int $id = null;
    public string $username;
    public ?string $password = null;
    public ?string $name_first = null;
    public ?string $name_last = null;
    public string $role_id = 'user';
    public ?DateTime $disabled = null;
    public ?DateTime $expires = null;

    public function __construct(
        string $username,
        Settings $settings,
        Database $database
    ) {
        parent::__construct($settings, $database);

        $this->username = $username;
    }

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database
    ): self {
        if (!isset($row['username'])) {
            throw new InvalidDataException('The username must be specified');
        }

        $user = new static(\trim($row['username']), $settings, $database);

        if (isset($row['id'])) {
            $user->id = (int) $row['id'];
        }

        if (isset($row['password'])) {
            $user->password = \trim($row['password']);
        }

        if (isset($row['name_first'])) {
            $user->name_first = \trim($row['name_first']);
        }

        if (isset($row['name_last'])) {
            $user->name_last = \trim($row['name_last']);
        }

        if (isset($row['role_id'])) {
            $user->role_id = \trim($row['role_id']);
        }

        if (isset($row['disabled'])) {
            $user->disabled = self::toDatetime($row['disabled']);
        }

        if (isset($row['expires'])) {
            $user->expires = self::toDatetime($row['expires']);
        }

        return $user;
    }

    /** @throws InvalidDataException */
    public static function fromId(
        int $id,
        Settings $settings,
        Database $database
    ): ?self {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from($database->prefix . 'user')
                ->where(field('id')->eq($id))
        );

        /** @var array<string, string|null>|null $row */
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (\is_array($row)) {
            return self::fromRow($row, $settings, $database);
        }

        return null;
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    public static function fromUsername(
        string $username,
        Settings $settings,
        Database $database
    ): ?self {
        if (\filter_var($username, \FILTER_VALIDATE_EMAIL) === false) {
            \assert($settings['ldap'] instanceof LdapConfig);

            if (!$settings['ldap']->enabled) {
                throw new InvalidDataException(
                    'Username is not an email address'
                );
            }

            $settings['ldap']->testDomain();

            $username .= '@' . $settings['ldap']->domain;
        }

        $statement = $database->execute(
            $database->query
                ->select()
                ->from($database->prefix . 'user')
                ->where(field('username')->eq($username))
        );

        /** @var array<string, string|null>|null $row */
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (\is_array($row)) {
            return self::fromRow($row, $settings, $database);
        }

        return null;
    }

    /** @throws InvalidDataException */
    public function getAuthChecksum(): string
    {
        try {
            $now = new DateTimeImmutable();
        } catch (Throwable $exception) {
            throw new InvalidDataException(
                'Could not determine current datetime',
                0,
                $exception
            );
        }

        /** @var string */
        return \hash(
            'sha256',
            (string) $this->id .
            (string) $this->password .
            (\is_null($this->disabled) || $now < $this->disabled
                ? 'enabled' : 'disabled') .
            (!\is_null($this->expires) && $now < $this->expires
                ? $this->expires->format('Y-m-d') : 'expired')
        );
    }

    public function getAuthId(): string
    {
        return (string) $this->id;
    }

    /** @return int|string|int[]|string[] */
    public function getAuthRole(?Context $context = null)
    {
        if ($context instanceof ContextData) {
            if (\is_null($context->user_id) && \is_int($this->id)) {
                $context->findRoleId($this->id);
            }

            if (\is_string($context->role_id) && $context->role_id !== '') {
                return $context->role_id;
            }
        }

        return $this->role_id;
    }

    public function isDisabled(): bool
    {
        return !\is_null($this->disabled) &&
            $this->disabled <= new DateTimeImmutable();
    }

    /** @throws \PDOException */
    public function remove(): void
    {
        $this->database->delete('user', ['id' => $this->id]);
    }

    public function requiresMfa(): bool
    {
        return false;
    }

    /**
     * @throws \PDOException
     * @throws DuplicateInsertException
     */
    public function save(): void
    {
        $format = fn (?DateTime $datetime): ?string => \is_null($datetime)
            ? null : $datetime->format('Y-m-d H:i:s');

        $map = [
            'username' => $this->username,
            'password' => $this->password,
            'name_first' => $this->name_first,
            'name_last' => $this->name_last,
            'role_id' => $this->role_id,
            'disabled' => $format($this->disabled),
            'expires' => $format($this->expires),
        ];

        if (!\is_null($this->id)) {
            $this->database->update('user', $map, 'id', $this->id);

            return;
        }

        $this->id = $this->database->insert('user', $map);
    }

    /**
     * @throws InvalidConfigException
     * @throws PasswordException
     */
    public function setPassword(?string $password = null): void
    {
        $this->password = null;

        if (!\is_string($password) || $password === '') {
            return;
        }

        $this->password = password_hash($password, \PASSWORD_DEFAULT);
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    public function verifyPassword(string $password): bool
    {
        if ($this->verifyPasswordLdap($password)) {
            return true;
        }

        if (!\is_string($this->password) || $this->password === '') {
            return false;
        }

        return \password_verify($password, $this->password);
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    private function verifyPasswordLdap(string $password): bool
    {
        \assert($this->settings['ldap'] instanceof LdapConfig);

        if (!$this->settings['ldap']->enabled) {
            return false;
        }

        $this->settings['ldap']->testDomain();

        [$username, $domain] = \explode('@', $this->username, 2);

        if ($domain !== $this->settings['ldap']->domain) {
            return false;
        }

        $this->settings['ldap']->testHost();

        $uri = 'ldap://' . $this->settings['ldap']->host;

        if (\is_int($this->settings['ldap']->port)) {
            $uri .= ':' . (string) $this->settings['ldap']->port;
        }

        $ldap = \ldap_connect($uri);

        if ($ldap === false) {
            throw new InvalidDataException('Could not set up LDAP connection');
        }

        if ($this->settings['ldap']->start_tls) {
            if (!\ldap_start_tls($ldap)) {
                throw new InvalidDataException(
                    'Could not start TLS for LDAP connection'
                );
            }
        }

        $this->settings['ldap']->testBind();

        try {
            // phpcs:ignore
            @ldap_bind(
                $ldap,
                sprintf($this->settings['ldap']->bind, $username),
                $password
            );
        } catch (StringsException $exception) {
            throw new InvalidDataException(
                'Could not parse LDAP binding',
                0,
                $exception
            );
        } catch (LdapException $exception) {
            return false;
        }

        return true;
    }

    /** @throws InvalidDataException */
    private static function toDatetime(string $data): DateTime
    {
        try {
            return new DateTimeImmutable($data);
        } catch (\Throwable $exception) {
            throw new InvalidDataException(
                'The date could not be parsed',
                0,
                $exception
            );
        }
    }
}
