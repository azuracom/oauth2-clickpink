<?php

namespace Azuracom\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * @method ClickPinkUser getResourceOwner(AccessToken $token)
 */
class ClickPink extends AbstractProvider
{

    use BearerAuthorizationTrait;
    
    private ?string $baseUrl = null;

    public function __construct($options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);
        if (!isset($options['environment'])) {
            throw new \InvalidArgumentException('The "environment" option must be provided using the provider_options.');
        }

        $this->baseUrl = $options['environment'] === 'prod' ? 'https://clickpink.net' : 'https://preprod.clickpink.net';
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->baseUrl . '/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->baseUrl . '/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->baseUrl . '/api/me';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $code = 0;
            $error = sprintf('%s: %s', $data['error'], $data['error_description']);

            throw new IdentityProviderException($error, $code, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ClickPinkUser($response);
    }
}
