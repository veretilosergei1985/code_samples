<?php

namespace CardPrinterService\Service\Http;

use GuzzleHttp\ClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MobilityAccountClient
{
    public function __construct(
        private readonly ClientInterface $keycloakClient,
        private readonly ClientInterface $mobilityAccountClient,
        private readonly CacheInterface $cache,
        private readonly string $keycloakRealm,
        private readonly string $keycloakClientId,
        private readonly string $keycloakClientSecret
    ) {
    }

    public function get(string $uri, array $options = []): mixed
    {
        return $this->request('GET', $uri, $options);
    }

    public function request(string $method, string $uri, array $options = []): mixed
    {
        $options['headers']['Authorization'] = 'Bearer '.$this->getAccessToken();
        $response = $this->mobilityAccountClient->request($method, $uri, $options);

        if (str_contains($response->getHeader('Content-Type')[0], 'image')) {
            return $response->getBody()->getContents();
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function getAccessToken(): mixed
    {
        return $this->cache->get('mobility_account_access_token', function (ItemInterface $item) {
            $response = $this->keycloakClient->request('POST', sprintf('/auth/realms/%s/protocol/openid-connect/token', $this->keycloakRealm), [
                'form_params' => [
                    'client_id' => $this->keycloakClientId,
                    'grant_type' => 'client_credentials',
                    'client_secret' => $this->keycloakClientSecret,
                    'scope' => 'openid',
                ],
            ]);
            /** @var array $result */
            $result = json_decode($response->getBody(), true);
            $item->expiresAfter((int) $result['expires_in']);

            return $result['access_token'];
        });
    }
}
