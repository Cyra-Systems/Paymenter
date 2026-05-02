<?php

namespace App\Services\Domains;

use App\Services\Domains\Exceptions\EnomException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

/**
 * Enom Reseller API client.
 *
 * Wraps the legacy interface.asp endpoint that Enom exposes for reseller
 * domain operations. Every command is a GET with `command=...` plus account
 * credentials and per-command parameters.
 *
 * Docs: https://api.enom.com/docs/domains
 *
 * Switch to the live endpoint by setting `enom.environment=production`
 * (otherwise reseller-test is used).
 */
class EnomClient
{
    public const ENV_LIVE = 'production';
    public const ENV_TEST = 'test';

    protected string $endpoint;

    public function __construct(
        protected string $username,
        protected string $password,
        string $environment = self::ENV_TEST,
        protected ?string $proxyUrl = null,
        protected int $timeout = 30,
    ) {
        $this->endpoint = $environment === self::ENV_LIVE
            ? 'https://reseller.enom.com/interface.asp'
            : 'https://resellertest.enom.com/interface.asp';
    }

    // -- Availability / pricing ------------------------------------------------

    public function check(string $sld, string $tld): array
    {
        return $this->call('Check', compact('sld', 'tld'));
    }

    public function checkMany(array $domains): array
    {
        return $this->call('Check', ['DomainList' => implode(',', $domains)]);
    }

    public function nameSpinner(string $sld, string $tld, int $maxResults = 20): array
    {
        return $this->call('NameSpinner', [
            'sld' => $sld,
            'tld' => $tld,
            'MaxResults' => $maxResults,
            'SensitiveContent' => 'False',
        ]);
    }

    public function getTldPrice(string $tld, string $productType = 'Register', int $years = 1): array
    {
        return $this->call('PE_GetRetailPrice', [
            'TLD' => $tld,
            'ProductType' => $productType,
            'Years' => $years,
        ]);
    }

    // -- Registration / transfer / renewal -------------------------------------

    public function purchase(string $sld, string $tld, int $numYears, array $contacts, array $extra = []): array
    {
        return $this->call('Purchase', array_merge([
            'sld' => $sld,
            'tld' => $tld,
            'NumYears' => $numYears,
        ], $contacts, $extra));
    }

    public function transfer(string $sld, string $tld, string $authInfo, array $contacts = [], array $extra = []): array
    {
        return $this->call('TP_CreateOrder', array_merge([
            'sld' => $sld,
            'tld' => $tld,
            'AuthInfo' => $authInfo,
            'OrderType' => 'AutoVerification',
            'UseContacts' => '0',
        ], $contacts, $extra));
    }

    public function transferStatus(int $orderId): array
    {
        return $this->call('TP_GetOrderDetail', ['TransferOrderID' => $orderId]);
    }

    public function renew(string $sld, string $tld, int $numYears = 1): array
    {
        return $this->call('Extend', ['sld' => $sld, 'tld' => $tld, 'NumYears' => $numYears]);
    }

    public function reactivate(string $sld, string $tld): array
    {
        return $this->call('UpdateExpiredDomains', compact('sld', 'tld'));
    }

    public function delete(string $sld, string $tld): array
    {
        return $this->call('DeleteRegistration', compact('sld', 'tld'));
    }

    // -- Domain info -----------------------------------------------------------

    public function getDomainInfo(string $sld, string $tld): array
    {
        return $this->call('GetDomainInfo', compact('sld', 'tld'));
    }

    public function listDomains(int $start = 1, int $records = 25): array
    {
        return $this->call('GetDomains', ['Start' => $start, 'Records' => $records]);
    }

    public function getExpirationDate(string $sld, string $tld): array
    {
        return $this->call('GetExpirationDate', compact('sld', 'tld'));
    }

    // -- Nameservers -----------------------------------------------------------

    public function getDns(string $sld, string $tld): array
    {
        return $this->call('GetDNS', compact('sld', 'tld'));
    }

    public function modifyNs(string $sld, string $tld, array $nameservers): array
    {
        $params = ['sld' => $sld, 'tld' => $tld];

        if (empty($nameservers)) {
            $params['NS1'] = 'dns1.name-services.com';
            $params['NS2'] = 'dns2.name-services.com';
        } else {
            $i = 1;
            foreach (array_filter($nameservers) as $ns) {
                if ($i > 12) break;
                $params['NS' . $i] = $ns;
                $i++;
            }
        }

        return $this->call('ModifyNS', $params);
    }

    public function modifyNsToDefault(string $sld, string $tld): array
    {
        return $this->call('ModifyNSDefault', compact('sld', 'tld'));
    }

    // -- Host records ----------------------------------------------------------

    public function getHosts(string $sld, string $tld): array
    {
        return $this->call('GetHosts', compact('sld', 'tld'));
    }

    public function setHosts(string $sld, string $tld, array $records): array
    {
        $params = ['sld' => $sld, 'tld' => $tld];
        $i = 1;
        foreach ($records as $r) {
            $params["HostName{$i}"] = $r['hostname'] ?? '@';
            $params["RecordType{$i}"] = $r['type'] ?? 'A';
            $params["Address{$i}"] = $r['address'] ?? '';
            $params["MXPref{$i}"] = $r['mxpref'] ?? 10;
            $params["TTL{$i}"] = $r['ttl'] ?? 3600;
            $i++;
        }
        return $this->call('SetHosts', $params);
    }

    // -- Forwarding ------------------------------------------------------------

    public function setDomainForwarding(string $sld, string $tld, string $forwardTo, string $type = '301'): array
    {
        $recordType = $type === '301' ? 'URL301' : ($type === 'frame' ? 'FRAME' : 'URL');
        return $this->setHosts($sld, $tld, [
            ['hostname' => '@', 'type' => $recordType, 'address' => $forwardTo],
            ['hostname' => 'www', 'type' => $recordType, 'address' => $forwardTo],
        ]);
    }

    public function getEmailForwarding(string $sld, string $tld): array
    {
        return $this->call('GetEmailFwding', compact('sld', 'tld'));
    }

    public function setEmailForwarding(string $sld, string $tld, array $forwards): array
    {
        $params = ['sld' => $sld, 'tld' => $tld];
        $i = 1;
        foreach ($forwards as $box => $target) {
            $params["MailBox{$i}"] = $box;
            $params["ForwardTo{$i}"] = $target;
            $i++;
        }
        return $this->call('SetEmailFwding', $params);
    }

    // -- Contacts --------------------------------------------------------------

    public function getContacts(string $sld, string $tld): array
    {
        return $this->call('GetContacts', compact('sld', 'tld'));
    }

    public function updateContacts(string $sld, string $tld, array $contacts): array
    {
        return $this->call('Contacts', array_merge(compact('sld', 'tld'), $contacts));
    }

    // -- Locking ---------------------------------------------------------------

    public function getRegLock(string $sld, string $tld): array
    {
        return $this->call('GetRegLock', compact('sld', 'tld'));
    }

    public function setRegLock(string $sld, string $tld, bool $locked = true): array
    {
        return $this->call('SetRegLock', [
            'sld' => $sld,
            'tld' => $tld,
            'UnlockRegistrar' => $locked ? '0' : '1',
        ]);
    }

    // -- Auto-renew ------------------------------------------------------------

    public function setAutoRenew(string $sld, string $tld, bool $enable = true): array
    {
        return $this->call('SetRenew', [
            'sld' => $sld,
            'tld' => $tld,
            'RenewFlag' => $enable ? '1' : '0',
        ]);
    }

    // -- ID Protect / WHOIS Privacy -------------------------------------------

    public function purchaseIdProtect(string $sld, string $tld, bool $enable = true): array
    {
        return $this->call($enable ? 'WPPSPurchase' : 'WPPSUpdate', [
            'sld' => $sld,
            'tld' => $tld,
            'isWPPSon' => $enable ? '1' : '0',
        ]);
    }

    // -- EPP / Auth-info -------------------------------------------------------

    public function getAuthInfo(string $sld, string $tld): array
    {
        return $this->call('SynchAuthInfo', [
            'sld' => $sld,
            'tld' => $tld,
            'EmailEPP' => 'False',
            'RunSynchAutoInfo' => 'True',
        ]);
    }

    // -- Account ---------------------------------------------------------------

    public function getBalance(): array
    {
        return $this->call('GetBalance');
    }

    // -- Low-level call --------------------------------------------------------

    public function call(string $command, array $params = []): array
    {
        $query = array_merge([
            'uid' => $this->username,
            'pw' => $this->password,
            'responsetype' => 'xml',
            'command' => $command,
        ], $params);

        $url = $this->proxyUrl ?: $this->endpoint;

        $response = Http::timeout($this->timeout)
            ->retry(2, 1000, throw: false)
            ->get($url, $query);

        if (!$response->ok()) {
            throw new EnomException(
                "Enom transport error ({$response->status()}) for command [{$command}]",
                $response->status()
            );
        }

        $data = $this->parseXml($response->body());

        $errCount = (int) Arr::get($data, 'ErrCount', 0);
        if ($errCount > 0) {
            $errors = [];
            foreach ($data['errors'] ?? [] as $v) {
                $errors[] = is_array($v) ? json_encode($v) : (string) $v;
            }
            throw new EnomException(
                "Enom API error on [{$command}]: " . (implode('; ', $errors) ?: 'unknown'),
                500,
                null,
                $data
            );
        }

        return $data;
    }

    protected function parseXml(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);
        libxml_use_internal_errors($previous);

        if ($sx === false) {
            throw new EnomException('Enom returned malformed XML');
        }

        $array = json_decode(json_encode($sx), true);

        if (isset($array['errors']) && is_array($array['errors'])) {
            $array['errors'] = array_filter(array_values($array['errors']), fn ($v) => $v !== [] && $v !== null);
        }

        return $array ?: [];
    }
}
