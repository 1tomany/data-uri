<?php

namespace OneToMany\DataUri\Source;

use OneToMany\DataUri\Contract\Source\UrlPolicyInterface;
use OneToMany\DataUri\Exception\InvalidArgumentException;
use OneToMany\DataUri\Exception\RuntimeException;

use function dns_get_record;
use function filter_var;
use function is_array;
use function is_string;
use function parse_url;
use function sprintf;
use function strtolower;
use function trim;

use const DNS_A;
use const DNS_AAAA;
use const FILTER_FLAG_NO_PRIV_RANGE;
use const FILTER_FLAG_NO_RES_RANGE;
use const FILTER_VALIDATE_IP;
use const PHP_URL_HOST;

/**
 * Rejects localhost and hosts resolving to private or reserved addresses.
 *
 * Redirects are disabled by SourceResolver so each fetched URL is checked once.
 */
final readonly class PublicUrlPolicy implements UrlPolicyInterface
{
    public function assertAllowed(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || '' === $host = trim($host, '[]')) {
            throw new InvalidArgumentException(sprintf('The URL "%s" does not have a valid host.', $url));
        }

        $host = strtolower($host);

        if ('localhost' === $host || str_ends_with($host, '.localhost')) {
            throw new InvalidArgumentException(sprintf('The URL host "%s" is not public.', $host));
        }

        if (false !== filter_var($host, FILTER_VALIDATE_IP)) {
            $this->assertPublicIp($host);

            return;
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA);

        if (!is_array($records) || [] === $records) {
            throw new RuntimeException(sprintf('Resolving the URL host "%s" failed.', $host));
        }

        $addresses = [];

        foreach ($records as $record) {
            foreach (['ip', 'ipv6'] as $key) {
                if (is_string($record[$key] ?? null)) {
                    $addresses[] = $record[$key];
                }
            }
        }

        if ([] === $addresses) {
            throw new RuntimeException(sprintf('Resolving an address for the URL host "%s" failed.', $host));
        }

        foreach ($addresses as $address) {
            $this->assertPublicIp($address);
        }
    }

    private function assertPublicIp(string $address): void
    {
        if (false === filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new InvalidArgumentException(sprintf('The URL address "%s" is private or reserved.', $address));
        }
    }
}
