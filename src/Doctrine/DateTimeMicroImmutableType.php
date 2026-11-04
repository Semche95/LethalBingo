<?php

declare(strict_types=1);

namespace App\Doctrine;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\Exception\InvalidFormat;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Exception;

/**
 * Immutable datetime kept to the microsecond, the default datetime type
 * dropping the fraction of a second (docs/TIMER.md section 2).
 */
class DateTimeMicroImmutableType extends DateTimeImmutableType
{
    public const string NAME = 'datetime_micro_immutable';

    private const string FORMAT = 'Y-m-d H:i:s.u';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform instanceof PostgreSQLPlatform
            ? 'TIMESTAMP(6) WITHOUT TIME ZONE'
            : parent::getSQLDeclaration($column, $platform);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value->format(self::FORMAT);
        }

        throw InvalidType::new($value, static::class, ['null', DateTimeImmutable::class]);
    }

    /**
     * @throws ConversionException
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?DateTimeImmutable
    {
        if ($value === null || $value instanceof DateTimeImmutable) {
            return $value;
        }

        // PostgreSQL leaves out the fraction when it is zero.
        $dateTime = DateTimeImmutable::createFromFormat(self::FORMAT, $value)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

        if ($dateTime !== false) {
            return $dateTime;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception $exception) {
            throw InvalidFormat::new($value, static::class, self::FORMAT, $exception);
        }
    }
}
