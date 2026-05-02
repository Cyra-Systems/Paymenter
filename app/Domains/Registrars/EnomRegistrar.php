<?php

namespace App\Domains\Registrars;

use App\Models\Domain;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class EnomRegistrar implements RegistrarContract
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [
            'username' => config('settings.domains.enom_username'),
            'password' => config('settings.domains.enom_password'),
            'sandbox' => (bool) config('settings.domains.enom_sandbox', true),
        ];
    }

    private function apiUrl(): string
    {
        return ! empty($this->config['sandbox'])
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    public function request(string $command, array $params = []): array
    {
        $response = Http::timeout(30)->get($this->apiUrl(), array_merge([
            'command' => $command,
            'uid' => $this->config['username'] ?? '',
            'pw' => $this->config['password'] ?? '',
            'responsetype' => 'xml',
        ], $params));

        if (! $response->successful()) {
            throw new Exception('Failed to connect to the Enom API: HTTP '.$response->status());
        }

        $xml = simplexml_load_string($response->body());
        if ($xml === false) {
            throw new Exception('Failed to parse the Enom API response.');
        }

        $result = json_decode(json_encode($xml), true) ?: [];
        $errCount = (int) ($result['ErrCount'] ?? 0);

        if ($errCount > 0) {
            $errors = $result['errors'] ?? [];
            $message = $errors['Err1'] ?? $result['Err1'] ?? 'Unknown Enom API error.';
            throw new Exception((string) $message);
        }

        return $result;
    }

    public function check(string $sld, string $tld): bool
    {
        $result = $this->request('Check', [
            'SLD' => strtolower(trim($sld)),
            'TLD' => strtolower(trim($tld)),
        ]);

        return (int) ($result['RRPCode'] ?? 0) === 210;
    }

    public function register(Domain $domain, int $years, array $contacts, array $options = []): array
    {
        if (! $this->check($domain->sld, $domain->tld)) {
            throw new Exception('Domain '.$domain->fqdn.' is not available for registration.');
        }

        $params = array_merge([
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
            'NumYears' => $years,
            'UseDNS' => $options['use_dns'] ?? 'default',
        ], $contacts);

        if (! empty($options['id_protect'])) {
            $params['AddPrivacy'] = 1;
        }

        $purchase = $this->request('Purchase', $params);

        if ((int) ($purchase['RRPCode'] ?? 0) !== 200) {
            $message = $purchase['RRPText'] ?? ('Domain registration failed for '.$domain->fqdn.'.');
            throw new Exception((string) $message);
        }

        $this->applyPostRegistrationOptions($options, $domain->sld, $domain->tld);

        return $purchase;
    }

    public function renew(Domain $domain, int $years): array
    {
        $result = $this->request('Extend', [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
            'NumYears' => $years,
        ]);

        if ((int) ($result['RRPCode'] ?? 0) !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Domain renewal failed for '.$domain->fqdn.'.'));
        }

        return $result;
    }

    public function transferIn(Domain $domain, string $authCode): array
    {
        $result = $this->request('TP_CreateOrder', [
            'SLD1' => $domain->sld,
            'TLD1' => $domain->tld,
            'AuthInfo1' => $authCode,
            'OrderType' => 'Autoverification',
            'UseContacts' => 1,
        ]);

        return $result;
    }

    public function getTransferStatus(Domain $domain): array
    {
        return $this->request('TP_GetOrderStatuses', [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
        ]);
    }

    public function getInfo(Domain $domain): array
    {
        $result = $this->request('GetDomainInfo', [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
        ]);

        return [
            'expires_at' => $this->parseDate($result, ['ExpirationDate', 'Expiration', 'ExpireDate', 'ExpDate']),
            'locked' => $this->parseBool($result, ['RegistrarLock', 'RegLock', 'Lock']),
            'auth_code' => $this->parseString($result, ['AuthInfo', 'AuthCode', 'EPPKey', 'TransferKey']),
            'auto_renew' => $this->parseBool($result, ['AutoRenew']),
            'status' => $this->parseString($result, ['Status', 'RRPText']),
            'nameservers' => $this->parseNameservers($result),
            'raw' => $result,
        ];
    }

    public function setNameservers(Domain $domain, array $nameservers): array
    {
        $normalized = array_values(array_filter(array_map(
            fn ($v) => $this->normalizeNameserver((string) $v),
            $nameservers,
        )));

        if (count($normalized) < 2) {
            throw new Exception('At least two valid nameservers are required.');
        }

        $params = [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
        ];
        foreach ($normalized as $i => $ns) {
            $params['NS'.($i + 1)] = $ns;
        }

        $result = $this->request('ModifyNS', $params);
        if ((int) ($result['RRPCode'] ?? 200) !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Unable to update nameservers right now.'));
        }

        return $result;
    }

    public function setLock(Domain $domain, bool $lock): array
    {
        $result = $this->request('SetRegLock', [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
            'UnlockRegistrar' => $lock ? 0 : 1,
        ]);

        if ((int) ($result['RRPCode'] ?? 200) !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Unable to update the registrar lock right now.'));
        }

        return $result;
    }

    public function getDns(Domain $domain): array
    {
        $result = $this->request('GetDNS', [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
        ]);

        return $this->parseDnsPayload($result);
    }

    public function setDns(Domain $domain, array $records): array
    {
        if ($records === []) {
            throw new Exception('Add at least one DNS record before saving.');
        }

        $params = [
            'SLD' => $domain->sld,
            'TLD' => $domain->tld,
        ];

        foreach (array_values($records) as $i => $record) {
            $position = $i + 1;
            $type = $this->normalizeDnsType($record['type'] ?? '');
            $address = trim((string) ($record['address'] ?? $record['value'] ?? ''));

            if ($type === '' || $address === '') {
                throw new Exception('Each DNS record needs a type and value.');
            }

            $params['HostName'.$position] = $this->normalizeDnsHostname($record['hostname'] ?? '@');
            $params['RecordType'.$position] = $type;
            $params['Address'.$position] = $address;

            if (isset($record['priority']) && $record['priority'] !== '' && $record['priority'] !== null) {
                $params['Priority'.$position] = (int) $record['priority'];
            }
        }

        $result = $this->request('SetDNSHost', $params);
        if ((int) ($result['RRPCode'] ?? 200) !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Unable to update DNS records right now.'));
        }

        return $this->getDns($domain);
    }

    public function testConnection(): bool|string
    {
        try {
            $response = $this->request('CheckLogin');
            $status = strtolower((string) ($response['LoginStatus'] ?? ''));

            if ($status !== '' && $status !== 'success') {
                return 'Login failed: '.$status;
            }

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function applyPostRegistrationOptions(array $options, string $sld, string $tld): void
    {
        $this->request('SetRenew', [
            'SLD' => $sld,
            'TLD' => $tld,
            'AutoRenew' => ! empty($options['auto_renew']) ? 1 : 0,
        ]);

        if (! empty($options['lock'])) {
            $this->request('SetRegLock', [
                'SLD' => $sld,
                'TLD' => $tld,
                'UnlockRegistrar' => 0,
            ]);
        }
    }

    public function buildContact(string $prefix, $user): array
    {
        $name = trim((string) ($user->name ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [];
        $firstName = $parts[0] ?: 'Domain';
        $lastName = $parts[1] ?? 'Owner';

        return [
            "{$prefix}FirstName" => $firstName,
            "{$prefix}LastName" => $lastName,
            "{$prefix}EmailAddress" => (string) ($user->email ?? 'noreply@example.com'),
            "{$prefix}Phone" => (string) ($user->phone ?? '+1.5555555555'),
            "{$prefix}Address1" => (string) ($user->address ?? '123 Main Street'),
            "{$prefix}City" => (string) ($user->city ?? 'Unknown'),
            "{$prefix}StateProvince" => (string) ($user->state ?? 'NA'),
            "{$prefix}PostalCode" => (string) ($user->postcode ?? '00000'),
            "{$prefix}Country" => strtoupper((string) ($user->country ?? 'US')),
        ];
    }

    public function fullContactSet($user): array
    {
        return array_merge(
            $this->buildContact('Registrant', $user),
            $this->buildContact('Admin', $user),
            $this->buildContact('Tech', $user),
            $this->buildContact('AuxBilling', $user),
        );
    }

    public function normalizeNameserver(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;
        $value = trim($value, '.');

        return $value !== '' ? $value : null;
    }

    private function normalizeDnsHostname(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '@') {
            return '@';
        }

        return trim($value, '.');
    }

    private function normalizeDnsType(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    private function normalizedResult(array $result): array
    {
        $normalized = [];
        foreach ($result as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        return $normalized;
    }

    private function parseNameservers(array $result): array
    {
        $nameservers = [];
        $walk = function (array $node) use (&$walk, &$nameservers): void {
            foreach ($node as $key => $value) {
                $lowerKey = strtolower((string) $key);
                if (is_array($value)) {
                    $walk($value);
                    continue;
                }
                if (! preg_match('/^ns\d+$/', $lowerKey) && $lowerKey !== 'nameserver') {
                    continue;
                }
                $host = $this->normalizeNameserver((string) $value);
                if ($host) {
                    $nameservers[] = $host;
                }
            }
        };
        $walk($result);

        return array_values(array_unique($nameservers));
    }

    private function parseDate(array $result, array $keys): ?Carbon
    {
        $normalized = $this->normalizedResult($result);
        foreach ($keys as $key) {
            $value = trim((string) ($normalized[strtolower($key)] ?? ''));
            if ($value === '') {
                continue;
            }
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function parseBool(array $result, array $keys): ?bool
    {
        $normalized = $this->normalizedResult($result);
        foreach ($keys as $key) {
            if (! array_key_exists(strtolower($key), $normalized)) {
                continue;
            }
            $value = strtolower(trim((string) $normalized[strtolower($key)]));
            if (in_array($value, ['1', 'true', 'yes', 'on', 'locked', 'enabled'], true)) {
                return true;
            }
            if (in_array($value, ['0', 'false', 'no', 'off', 'unlocked', 'disabled'], true)) {
                return false;
            }
        }

        return null;
    }

    private function parseString(array $result, array $keys): ?string
    {
        $normalized = $this->normalizedResult($result);
        foreach ($keys as $key) {
            $value = trim((string) ($normalized[strtolower($key)] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function parseInteger(array $result, array $keys): ?int
    {
        $normalized = $this->normalizedResult($result);
        foreach ($keys as $key) {
            $raw = $normalized[strtolower($key)] ?? null;
            if ($raw === null || $raw === '') {
                continue;
            }
            if (is_numeric($raw)) {
                return (int) $raw;
            }
        }

        return null;
    }

    private function extractDnsRecords(array $node, array &$records): void
    {
        $normalized = $this->normalizedResult($node);
        $type = $this->normalizeDnsType($normalized['recordtype'] ?? $normalized['type'] ?? '');
        $address = trim((string) ($normalized['address'] ?? $normalized['addr'] ?? $normalized['value'] ?? $normalized['destination'] ?? $normalized['target'] ?? ''));

        if ($type !== '' && $address !== '') {
            $records[] = [
                'hostname' => $this->normalizeDnsHostname($normalized['hostname'] ?? $normalized['host'] ?? $normalized['name'] ?? '@'),
                'type' => $type,
                'address' => $address,
                'priority' => $this->parseInteger($normalized, ['priority', 'mxpref', 'preference']),
            ];
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->extractDnsRecords($value, $records);
            }
        }
    }

    private function parseDnsPayload(array $result): array
    {
        $records = [];
        $this->extractDnsRecords($result, $records);

        $unique = [];
        foreach ($records as $record) {
            $signature = implode('|', [
                $record['hostname'],
                $record['type'],
                $record['address'],
                (string) ($record['priority'] ?? ''),
            ]);
            $unique[$signature] = $record;
        }

        return [
            'nameservers' => $this->parseNameservers($result),
            'records' => array_values($unique),
            'raw' => $result,
        ];
    }
}
