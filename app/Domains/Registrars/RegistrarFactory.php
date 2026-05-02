<?php

namespace App\Domains\Registrars;

use App\Models\Domain;
use Exception;

class RegistrarFactory
{
    public static function for(Domain $domain): RegistrarContract
    {
        return self::byName($domain->registrar ?: 'enom');
    }

    public static function byName(string $name, ?array $config = null): RegistrarContract
    {
        return match ($name) {
            'enom' => new EnomRegistrar($config),
            default => throw new Exception('Unsupported registrar: '.$name),
        };
    }
}
