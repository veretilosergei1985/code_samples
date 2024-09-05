<?php

namespace CardPrinterService\Service\Provider;

use GuzzleHttp\ClientInterface;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

class CustomerConfigurationProvider
{
    public function __construct(private readonly ClientInterface $configurationServiceClient)
    {
    }

    /** @return array<mixed> */
    public function findByCustomerAndParameterName(UserDto $customer, string $parameterName): array
    {
        $options['headers']['Authorization'] = 'Bearer '.$customer->getAccessToken();
        $response = $this->configurationServiceClient->request('GET', sprintf('/customers?shortName=%s&readableLink=true', $customer->getCustomer()), $options);
        /** @var array $content */
        $content = json_decode($response->getBody()->getContents(), true);
        $this->assertContent($content);

        $filteredParameters = array_filter(
            $content['hydra:member'][0]['values'],
            static fn (array $parameter) => $parameter['parameter']['name'] === $parameterName
        );
        if (count($filteredParameters) === 0) {
            throw new \RuntimeException(sprintf('Array should contain %s parameter.', $parameterName));
        }

        return $filteredParameters[array_key_first($filteredParameters)];
    }

    /** @param array<mixed> $content */
    protected function assertContent(array $content): static
    {
        if (
            array_key_exists('hydra:member', $content) === false
            || count($content['hydra:member']) === 0
            || array_key_exists('values', $content['hydra:member'][0]) === false
        ) {
            throw new \RuntimeException('Api response is not expected.');
        }

        return $this;
    }
}
