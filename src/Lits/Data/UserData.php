<?php

declare(strict_types=1);

namespace Lits\Data;

use DateTimeInterface as DateTime;
use Jasny\Auth\ContextInterface as Context;
use Jasny\Auth\UserInterface as User;
use Lits\Connector\LdapConnector;
use Lits\Database;
use Lits\Exception\DuplicateInsertException;
use Lits\Exception\InvalidConfigException;
use Lits\Exception\InvalidDataException;
use Lits\Settings;
use Safe\DateTimeImmutable;
use Throwable;

use function Latitude\QueryBuilder\field;

final class UserData extends DatabaseData implements User
{
    public ?int $id = null;
    public ?string $password = null;
    public ?string $name_first = null;
    public ?string $name_last = null;
    public string $role_id = 'user';
    public ?DateTime $disabled = null;
    public ?DateTime $expires = null;

    public function __construct(
        public string $username,
        Settings $settings,
        Database $database,
    ) {
        parent::__construct($settings, $database);
    }

    /**
     * @param array<string, string|null> $row
     * @throws InvalidDataException
     */
    public static function fromRow(
        array $row,
        Settings $settings,
        Database $database,
    ): self {
        if (!isset($row['username'])) {
            throw new InvalidDataException('The username must be specified');
        }

        $user = new static(\trim($row['username']), $settings, $database);
        $user->id = self::findRowInt($row, 'id');
        $user->password = self::findRowString($row, 'password');
        $user->name_first = self::findRowString($row, 'name_first');
        $user->name_last = self::findRowString($row, 'name_last');
        $user->disabled = self::findRowDatetime($row, 'disabled');
        $user->expires = self::findRowDatetime($row, 'expires');

        if (isset($row['role_id'])) {
            $user->role_id = \trim($row['role_id']);
        }

        return $user;
    }

    /** @throws InvalidDataException */
    public static function fromId(
        int $id,
        Settings $settings,
        Database $database,
    ): ?self {
        $statement = $database->execute(
            $database->query
                ->select()
                ->from($database->prefix . 'user')
                ->where(field('id')->eq($id)),
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
        Database $database,
    ): ?self {
        $ldap = new LdapConnector($settings);

        if (\filter_var($username, \FILTER_VALIDATE_EMAIL) === false) {
            $username .= '@' . $ldap->domain();
        }

        $statement = $database->execute(
            $database->query
                ->select()
                ->from($database->prefix . 'user')
                ->where(field('username')->eq($username)),
        );

        /** @var array<string, string|null>|null $row */
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (\is_array($row)) {
            return self::fromRow($row, $settings, $database);
        }

        return null;
    }

    /** @throws InvalidDataException */
    #[\Override]
    public function getAuthChecksum(): string
    {
        try {
            $now = new DateTimeImmutable();
        } catch (Throwable $exception) {
            throw new InvalidDataException(
                'Could not determine current datetime',
                0,
                $exception,
            );
        }

        return \hash(
            'sha256',
            (string) $this->id .
            (string) $this->password .
            (\is_null($this->disabled) || $now < $this->disabled
                ? 'enabled' : 'disabled') .
            (!\is_null($this->expires) && $now < $this->expires
                ? $this->expires->format('Y-m-d') : 'expired'),
        );
    }

    #[\Override]
    public function getAuthId(): string
    {
        return (string) $this->id;
    }

    #[\Override]
    public function getAuthRole(?Context $context = null): string
    {
        if ($context instanceof ContextData) {
            if (\is_int($this->id)) {
                $context->findRoleId($this->id);
            }

            if (\is_string($context->role_id)) {
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

    #[\Override]
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

    /** @throws InvalidConfigException */
    public function setPassword(?string $password = null): void
    {
        $this->password = null;

        if (!\is_string($password) || $password === '') {
            return;
        }

        $this->password = \password_hash($password, \PASSWORD_DEFAULT);
    }

    /**
     * @throws InvalidConfigException
     * @throws InvalidDataException
     */
    #[\Override]
    public function verifyPassword(string $password): bool
    {
        $ldap = new LdapConnector($this->settings);

        if ($ldap->verify($this->username, $password)) {
            return true;
        }

        if (!\is_string($this->password) || $this->password === '') {
            return false;
        }

        return \password_verify($password, $this->password);
    }
}
