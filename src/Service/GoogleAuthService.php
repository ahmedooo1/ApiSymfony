<?php

namespace App\Service;

use League\OAuth2\Client\Provider\Google;
use Symfony\Component\HttpFoundation\RequestStack;
use GuzzleHttp\Client;

class GoogleAuthService
{
    private Google $googleClient;
    private RequestStack $requestStack;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri, RequestStack $requestStack)
    {
        $this->googleClient = new Google([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri'  => $redirectUri,
            'httpClient'   => new Client(['verify' => 'C:\\Users\\Etudiant\\Documents\\3BCI-ProjetFinal\\ApiSymfony\\cacert.pem']),
        ]);
        $this->requestStack = $requestStack;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->googleClient->getAuthorizationUrl();
    }

    public function getAccessToken(string $code)
    {
        return $this->googleClient->getAccessToken('authorization_code', [
            'code' => $code,
        ]);
    }

    public function getUserInfo($token)
    {
        return $this->googleClient->getResourceOwner($token);
    }
}
