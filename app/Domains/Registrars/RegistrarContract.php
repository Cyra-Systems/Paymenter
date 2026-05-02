<?php

namespace App\Domains\Registrars;

use App\Models\Domain;

interface RegistrarContract
{
    public function check(string $sld, string $tld): bool;

    public function register(Domain $domain, int $years, array $contacts, array $options = []): array;

    public function renew(Domain $domain, int $years): array;

    public function transferIn(Domain $domain, string $authCode): array;

    public function getTransferStatus(Domain $domain): array;

    public function getInfo(Domain $domain): array;

    public function setNameservers(Domain $domain, array $nameservers): array;

    public function setLock(Domain $domain, bool $lock): array;

    public function getDns(Domain $domain): array;

    public function setDns(Domain $domain, array $records): array;

    public function testConnection(): bool|string;
}
