<?php

namespace SimpleMDB\Exceptions;

/**
 * Exception thrown when SimpleMySQLi operations fail
 */
class SimpleMySQLiException extends SimpleMDBException
{
    public static function invalidFetchType(string $fetchType, array $allowed): self
    {
        $allowedComma = implode("','", $allowed);
        return new self("The variable 'fetchType' must be '$allowedComma'. You entered '$fetchType'");
    }

    public static function classNameWithoutObjFetch(): self
    {
        return new self("You can only specify a class name with 'obj' as the fetch type");
    }

    public static function columnCountMismatch(string $fetchType, int $expected): self
    {
        return new self("The fetch type: '$fetchType' must have exactly $expected column(s) in query");
    }

    public static function parameterMismatch(string $param1, string $param2, int $count1, int $count2): self
    {
        return new self("The parameters '$param1' and '$param2' must correlate. You entered '$param1' array count: $count1 and '$param2' array count: $count2");
    }

    public static function queryFailed(int $affectedRows, string $query): self
    {
        return new self("Query did not succeed, with affectedRows() of: $affectedRows Query: $query");
    }
} 