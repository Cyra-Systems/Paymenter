<?php

namespace Paymenter\Extensions\Servers\Enom;

use App\Attributes\ExtensionMeta;
use App\Classes\Extension\Server;
use App\Helpers\ExtensionHelper;
use App\Models\Service;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

#[ExtensionMeta(
    name: 'Enom Domain Registrar',
    description: 'Register and renew domains through the Enom XML API.',
    version: '1.1.0',
    author: 'Paymenteer',
    url: '',
    icon: ''
)]
class Enom extends Server
{
    private function companionStatusDescription(): string
    {
        try {
            ExtensionHelper::getExtension('other', 'EnomDomains');

            return 'Optional companion detected: Enom Search is installed and can use this server for client-area search and synced TLD catalog features.';
        } catch (\Throwable) {
            return 'Optional companion not installed: install Enom Search if you want client-area domain search and synced TLD catalog features.';
        }
    }

    private function apiUrl(): string
    {
        return $this->config('sandbox')
            ? 'https://resellertest.enom.com/interface.asp'
            : 'https://reseller.enom.com/interface.asp';
    }

    private function request(string $command, array $params = []): array
    {
        $response = Http::timeout(30)->get($this->apiUrl(), array_merge([
            'command' => $command,
            'uid' => $this->config('username'),
            'pw' => $this->config('password'),
            'responsetype' => 'xml',
        ], $params));

        if (! $response->successful()) {
            throw new Exception('Failed to connect to the Enom API: HTTP ' . $response->status());
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

    private function getDomainParts(array $settings, array $properties): array
    {
        $sld = strtolower(trim((string) ($properties['domain'] ?? '')));
        $tld = strtolower(trim((string) ($settings['tld'] ?? '')));

        if ($sld === '' || $tld === '') {
            throw new Exception('Domain SLD or TLD is missing.');
        }

        return [
            'sld' => $sld,
            'tld' => $tld,
            'domain' => $sld . '.' . $tld,
        ];
    }

    private function domainAvailable(string $sld, string $tld): bool
    {
        $availability = $this->request('Check', [
            'SLD' => $sld,
            'TLD' => $tld,
        ]);

        return (int) ($availability['RRPCode'] ?? 0) === 210;
    }

    private function buildContact(string $prefix, object $user): array
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

    private function saveProperty(Service $service, string $key, string $name, string $value): void
    {
        $service->properties()->updateOrCreate([
            'key' => $key,
        ], [
            'name' => $name,
            'value' => $value,
        ]);
    }

    private function normalizedResult(array $result): array
    {
        $normalized = [];

        foreach ($result as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        return $normalized;
    }

    private function normalizeNameserver(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#/.*$#', '', $value) ?? $value;
        $value = trim($value, '.');

        return $value !== '' ? $value : null;
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

        $uniqueRecords = [];
        foreach ($records as $record) {
            $signature = implode('|', [
                $record['hostname'],
                $record['type'],
                $record['address'],
                (string) ($record['priority'] ?? ''),
            ]);

            $uniqueRecords[$signature] = $record;
        }

        return [
            'nameservers' => $this->parseNameservers($result),
            'records' => array_values($uniqueRecords),
            'raw' => $result,
        ];
    }

    private function applyPostRegistrationOptions(array $settings, string $sld, string $tld): void
    {
        $this->request('SetRenew', [
            'SLD' => $sld,
            'TLD' => $tld,
            'AutoRenew' => ! empty($settings['auto_renew']) ? 1 : 0,
        ]);

        if (! empty($settings['lock'])) {
            $this->request('SetRegLock', [
                'SLD' => $sld,
                'TLD' => $tld,
                'UnlockRegistrar' => 0,
            ]);
        }
    }

    public function getConfig($values = []): array
    {
        return [
            [
                'name' => 'username',
                'label' => 'Enom Username',
                'type' => 'text',
                'description' => 'Your Enom reseller username. ' . $this->companionStatusDescription(),
                'required' => true,
            ],
            [
                'name' => 'password',
                'label' => 'Enom Password / Token',
                'type' => 'password',
                'description' => 'Use the live or test API credential issued by Enom.',
                'required' => true,
                'encrypted' => true,
            ],
            [
                'name' => 'sandbox',
                'label' => 'Sandbox Mode',
                'type' => 'checkbox',
                'description' => 'Use the Enom test endpoint instead of the live reseller endpoint.',
            ],
        ];
    }

    public function getProductConfig($values = []): array
    {
        return [
            [
                'name' => 'tld',
                'label' => 'TLD',
                'type' => 'text',
                'description' => 'The extension of this domain product, for example com, net, org',
                'placeholder' => 'com',
                'required' => true,
            ],
            [
                'name' => 'years',
                'label' => 'Registration Period',
                'type' => 'number',
                'description' => 'How many years E should register or renew the domain for.',
                'required' => true,
                'default' => 1,
                'min_value' => 1,
                'max_value' => 10,
            ],
            [
                'name' => 'id_protect',
                'label' => 'WHOIS Privacy',
                'type' => 'checkbox',
                'description' => 'Requestprivacy protection during registration when the TLD supports it.',
            ],
            [
                'name' => 'auto_renew',
                'label' => 'Auto Renew',
                'type' => 'checkbox',
                'description' => 'Enable auto-renew on newly registered domains.',
            ],
            [
                'name' => 'lock',
                'label' => 'Registrar Lock',
                'type' => 'checkbox',
                'description' => 'Apply registrar lock after the domain is registered.',
            ],
        ];
    }

    public function getCheckoutConfig($product = null, $values = [], $settings = []): array
    {
        $availabilityValidation = [];
        $domain = strtolower(trim((string) ($values['domain'] ?? '')));
        $tld = strtolower(trim((string) ($settings['tld'] ?? '')));

        if ($domain !== '' && $tld !== '') {
            $availabilityValidation[] = function (string $attribute, mixed $value, \Closure $fail) use ($domain, $tld) {
                try {
                    if (! $this->domainAvailable($domain, $tld)) {
                        $fail('The selected domain is not available.');
                    }
                } catch (Exception $exception) {
                    $fail('We could not verify domain availability right now. Please try again in a moment.');
                }
            };
        }

        return [
            [
                'name' => 'domain',
                'label' => 'Domain Label',
                'type' => 'text',
                'description' => 'Enter only the name before the TLD. Example: use "example" for example.com.',
                'placeholder' => 'example',
                'required' => true,
                'validation' => array_merge([
                    'regex:/^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/',
                ], $availabilityValidation),
            ],
        ];
    }

    public function testConfig(): bool|string
    {
        try {
            $response = $this->request('CheckLogin');
            $status = strtolower((string) ($response['LoginStatus'] ?? ''));

            if ($status !== '' && $status !== 'success') {
                return 'Login failed: ' . $status;
            }

            return true;
        } catch (Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function createServer(Service $service, $settings, $properties)
    {
        ['sld' => $sld, 'tld' => $tld, 'domain' => $domain] = $this->getDomainParts($settings, $properties);

        if (! $this->domainAvailable($sld, $tld)) {
            throw new Exception('Domain ' . $domain . ' is not available for registration.');
        }

        $user = $service->user;
        $contacts = array_merge(
            $this->buildContact('Registrant', $user),
            $this->buildContact('Admin', $user),
            $this->buildContact('Tech', $user),
            $this->buildContact('AuxBilling', $user),
        );

        $params = array_merge([
            'SLD' => $sld,
            'TLD' => $tld,
            'NumYears' => (int) ($settings['years'] ?? 1),
            'UseDNS' => 'default',
        ], $contacts);

        if (! empty($settings['id_protect'])) {
            $params['AddPrivacy'] = 1;
        }

        $purchase = $this->request('Purchase', $params);

        if ((int) ($purchase['RRPCode'] ?? 0) !== 200) {
            $message = $purchase['RRPText'] ?? ('Domain registration failed for ' . $domain . '.');
            throw new Exception((string) $message);
        }

        $this->applyPostRegistrationOptions($settings, $sld, $tld);
        $this->saveProperty($service, 'enom_domain', 'Enom domain', $domain);

        return [
            'domain' => $domain,
            'status' => 'active',
        ];
    }

    public function suspendServer(Service $service, $settings, $properties)
    {
        return true;
    }

    public function unsuspendServer(Service $service, $settings, $properties)
    {
        return true;
    }

    public function terminateServer(Service $service, $settings, $properties)
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        $this->request('SetRenew', [
            'SLD' => $sld,
            'TLD' => $tld,
            'AutoRenew' => 0,
        ]);

        return true;
    }

    public function renewServer(Service $service, $settings, $properties)
    {
        ['sld' => $sld, 'tld' => $tld, 'domain' => $domain] = $this->getDomainParts($settings, $properties);

        $result = $this->request('Extend', [
            'SLD' => $sld,
            'TLD' => $tld,
            'NumYears' => (int) ($settings['years'] ?? 1),
        ]);

        if ((int) ($result['RRPCode'] ?? 0) !== 200) {
            $message = $result['RRPText'] ?? ('Domain renewal failed for ' . $domain . '.');
            throw new Exception((string) $message);
        }

        return true;
    }

    public function getDomainInfo(Service $service, $settings, $properties): array
    {
        ['sld' => $sld, 'tld' => $tld, 'domain' => $domain] = $this->getDomainParts($settings, $properties);

        $result = $this->request('GetDomainInfo', [
            'SLD' => $sld,
            'TLD' => $tld,
        ]);

        $expiresAt = $this->parseDate($result, ['ExpirationDate', 'Expiration', 'ExpireDate', 'ExpDate']);
        $lock = $this->parseBool($result, ['RegistrarLock', 'RegLock', 'Lock']);
        $authCode = $this->parseString($result, ['AuthInfo', 'AuthCode', 'EPPKey', 'TransferKey']);
        $autoRenew = $this->parseBool($result, ['AutoRenew']);
        $status = $this->parseString($result, ['Status', 'RRPText']);
        $nameservers = $this->parseNameservers($result);

        if ($expiresAt) {
            $service->forceFill([
                'expires_at' => $expiresAt,
            ])->save();
        }

        $this->saveProperty($service, 'enom_domain', 'Enom domain', $domain);

        foreach (range(1, 4) as $index) {
            $value = $nameservers[$index - 1] ?? '';
            $this->saveProperty($service, 'nameserver_' . $index, 'Nameserver ' . $index, $value);
        }

        if ($lock !== null) {
            $this->saveProperty($service, 'registrar_lock', 'Registrar Lock', $lock ? '1' : '0');
        }

        if ($authCode) {
            $this->saveProperty($service, 'transfer_auth_code', 'Transfer Auth Code', $authCode);
        }

        return [
            'domain' => $domain,
            'nameservers' => $nameservers,
            'locked' => $lock,
            'auth_code' => $authCode,
            'auto_renew' => $autoRenew,
            'status' => $status,
            'expires_at' => $expiresAt,
            'raw' => $result,
        ];
    }

    public function updateNameservers(Service $service, $settings, $properties, array $nameservers): array
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        $normalized = array_values(array_filter(array_map(
            fn ($value) => $this->normalizeNameserver((string) $value),
            $nameservers
        )));

        if (count($normalized) < 2) {
            throw new Exception('At least two valid nameservers are required.');
        }

        $params = [
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        foreach ($normalized as $index => $nameserver) {
            $params['NS' . ($index + 1)] = $nameserver;
        }

        $result = $this->request('ModifyNS', $params);
        $code = (int) ($result['RRPCode'] ?? 200);

        if ($code !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Unable to update nameservers right now.'));
        }

        return $this->getDomainInfo($service, $settings, $properties);
    }

    public function setRegistrarLock(Service $service, $settings, $properties, bool $lock): array
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        $result = $this->request('SetRegLock', [
            'SLD' => $sld,
            'TLD' => $tld,
            'UnlockRegistrar' => $lock ? 0 : 1,
        ]);

        $code = (int) ($result['RRPCode'] ?? 200);

        if ($code !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Unable to update the registrar lock right now.'));
        }

        return $this->getDomainInfo($service, $settings, $properties);
    }

    public function getDNS(Service $service, $settings, $properties): array
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        $result = $this->request('GetDNS', [
            'SLD' => $sld,
            'TLD' => $tld,
        ]);

        return $this->parseDnsPayload($result);
    }

    public function setDNS(Service $service, $settings, $properties, array $records): array
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        if ($records === []) {
            throw new Exception('Add at least one DNS record before saving.');
        }

        $params = [
            'SLD' => $sld,
            'TLD' => $tld,
        ];

        foreach (array_values($records) as $index => $record) {
            $position = $index + 1;
            $type = $this->normalizeDnsType($record['type'] ?? '');
            $address = trim((string) ($record['address'] ?? ''));

            if ($type === '' || $address === '') {
                throw new Exception('Each DNS record needs a type and value.');
            }

            $params['HostName' . $position] = $this->normalizeDnsHostname($record['hostname'] ?? '@');
            $params['RecordType' . $position] = $type;
            $params['Address' . $position] = $address;

            if (isset($record['priority']) && $record['priority'] !== '' && $record['priority'] !== null) {
                $params['Priority' . $position] = (int) $record['priority'];
            }
        }

        $result = $this->request('SetDNSHost', $params);
        $code = (int) ($result['RRPCode'] ?? 200);

        if ($code !== 200) {
            throw new Exception((string) ($result['RRPText'] ?? 'Unable to update DNS records right now.'));
        }

        return $this->getDNS($service, $settings, $properties);
    }

    public function getNameServers(Service $service, $settings, $properties): array
    {
        return $this->getDomainInfo($service, $settings, $properties)['nameservers'] ?? [];
    }

    public function setNameServers(Service $service, $settings, $properties, array $nameservers): bool
    {
        $this->updateNameservers($service, $settings, $properties, $nameservers);

        return true;
    }

    public function registerChildNS(Service $service, $settings, $properties, string $nameserver, string $ip): bool
    {
        $this->request('RegisterNameServer', [
            'NS' => $this->normalizeNameserver($nameserver),
            'IP' => trim($ip),
        ]);

        return true;
    }

    public function updateChildNS(Service $service, $settings, $properties, string $nameserver, string $oldIp, string $newIp): bool
    {
        $this->request('ModifyNameServer', [
            'NS' => $this->normalizeNameserver($nameserver),
            'OldIP' => trim($oldIp),
            'NewIP' => trim($newIp),
        ]);

        return true;
    }

    public function deleteChildNS(Service $service, $settings, $properties, string $nameserver): bool
    {
        $this->request('DeleteNameServer', [
            'NS' => $this->normalizeNameserver($nameserver),
        ]);

        return true;
    }

    public function getDNSSEC(Service $service, $settings, $properties): array
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        return $this->request('GetDNSSEC', [
            'SLD' => $sld,
            'TLD' => $tld,
        ]);
    }

    public function addDNSSEC(Service $service, $settings, $properties, array $data): bool
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        $this->request('AddDNSSEC', array_merge([
            'SLD' => $sld,
            'TLD' => $tld,
        ], $data));

        return true;
    }

    public function deleteDNSSEC(Service $service, $settings, $properties, array $data): bool
    {
        ['sld' => $sld, 'tld' => $tld] = $this->getDomainParts($settings, $properties);

        $this->request('DeleteDNSSEC', array_merge([
            'SLD' => $sld,
            'TLD' => $tld,
        ], $data));

        return true;
    }

    public function getActions(Service $service, $settings, $properties): array
    {
        try {
            ['domain' => $domain] = $this->getDomainParts($settings, $properties);
        } catch (Exception $exception) {
            return [];
        }

        return [
            [
                'label' => 'Open Enom',
                'type' => 'button',
                'url' => 'https://www.enomcentral.com/',
            ],
            [
                'label' => 'WHOIS Lookup',
                'type' => 'button',
                'url' => 'https://www.whois.com/whois/' . rawurlencode($domain),
            ],
        ];
    }

    public function checkDomain(string $sld, string $tld): bool
    {
        $result = $this->request('Check', [
            'SLD' => strtolower(trim($sld)),
            'TLD' => strtolower(trim($tld)),
        ]);

        return (int) ($result['RRPCode'] ?? 0) === 210;
    }
}
