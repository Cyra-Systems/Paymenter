<?php

namespace App\Services\Domains\Contracts;

use App\Models\Domain;
use App\Models\Service;

/**
 * Server / extension contract for products that need to know their hostname.
 *
 * When a Domain is bound (or re-bound) to a Service, every product extension
 * implementing this interface will be invoked so it can push the hostname
 * change down to its provider (cPanel, Pterodactyl, Plesk, custom store CMS,
 * Cloudflare, ...).
 */
interface HasHostname
{
    /** Apply a new hostname (FQDN) to the given service. */
    public function applyHostname(Service $service, Domain $domain, ?string $previousHostname = null): bool;

    /** Remove the hostname from the service. Implementations should be idempotent. */
    public function removeHostname(Service $service, ?string $hostname): bool;
}
