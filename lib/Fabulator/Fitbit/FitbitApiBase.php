<?php
namespace Fabulator\Fitbit;

use \GuzzleHttp\Client;
use \Psr\Http\Message\ResponseInterface;

/**
 * Class FitbitAPIBase
 * @package Fabulator\Fitbit
 */
class FitbitApiBase
{

    /**
     * @var string API url
     */
    const FITBIT_API_URL = 'https://api.fitbit.com/1/';

    /**
     * @var string
     */
    const FITBIT_OAUTH_URL = 'https://api.fitbit.com';

    /**
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string Fitbit client id
     */
    private $clientId;

    /**
     * @var string Fitbit secret ID
     */
    private $secret;

    /**
     * @var Client
     */
    private $client;

    /**
     * Fitbit constructor.
     *
     * @param string $clientId your client id
     * @param string $secret your secret key
     */
    public function __construct($clientId, $secret)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->client = new Client();
    }


    /**
     * Login url for users
     * https://dev.fitbit.com/docs/oauth2/#authorization-page
     *
     * @param string $redirectUri Where will be user redirected
     * @param array(activity, heartrate, location, nutrition, profile, settings, sleep, social, weight) $scope Array of scopes you require
     * @param string(code, token) $responseType type of Fitbit response
     * @param null|int $expiresIn Only for token response - time in sec
     * @param string(none|consent|login|login consent) $prompt type of user authorization
     * @param null|string $state This parameter will be added to the redirect URI exactly as your application specifies.
     * @return string url
     */
    public function getLoginUrl(
        $redirectUri,
        $scope,
        $responseType = 'code',
        $expiresIn = null,
        $prompt = 'none',
        $state = null)
    {
        $parameters = [
            'response_type' => $responseType,
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => join(' ', $scope),
            'prompt' => $prompt,
        ];

        if ($expiresIn !== null) {
            $parameters['expires_in'] = $expiresIn;
        }

        if ($state !== null) {
            $parameters['state'] = $state;
        }

        return self::FITBIT_OAUTH_URL . '?' . http_build_query($parameters);
    }

    /**
     * Request new Fitbit access token.
     *
     * @param string $code code from Fitbit
     * @param string $redirectUri redirect uri used to get code
     * @param int|null $expiresIn set length of token
     * @param string|null $state This parameter will be added to the redirect URI exactly as your application specifies.
     * @return ResponseInterface response from Fitbit API
     */
    public function requestAccessToken($code, $redirectUri, $expiresIn = null, $state = null)
    {
        $parameters = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
        ];

        if ($expiresIn != null) {
            $parameters['expires_in'] = $expiresIn;
        }

        if ($state != null) {
            $parameters['state'] = $state;
        }

        return $this->tokenRequest('oauth2/token', $parameters);
    }

    /**
     * Refresh Fitbit token.
     *
     * @param string $refreshToken refresh token
     * @param int|null $expiresIn set length of token
     * @return ResponseInterface response from Fitbit API
     */
    public function refreshAccessToken($refreshToken, $expiresIn = null)
    {
        $parameters = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];

        if ($expiresIn != null) {
            $parameters['expires_in'] = $expiresIn;
        }

        return $this->tokenRequest('oauth2/token', $parameters);
    }

    /**
     * Revoke Fitbit token.
     *
     * @param string $token token to revoke
     * @return ResponseInterface response from Fitbit API
     */
    public function revokeAccessToken($token)
    {
        $parameters = [
            'token' => $token,
        ];

        return $this->tokenRequest('oauth2/revoke', $parameters);
    }

    /**
     * Request token action.
     *
     * @param string $namespace namespace of request
     * @param array $parameters request parameters
     * @return ResponseInterface response from Fitbit API
     */
    private function tokenRequest($namespace, $parameters)
    {
        $client = new Client([
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '. base64_encode($this->clientId . ':' . $this->secret),
            ]
        ]);

        return $client->post(self::FITBIT_OAUTH_URL . '/' . $namespace . '?' . http_build_query($parameters));
    }

    /**
     * Set Fitbit token
     *
     * @param string $token token to set
     * @return FitbitApiBase
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get Fitbit token.
     *
     * @return string token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set custom headers for API requests.
     *
     * @param array $headers
     * @return FitbitApiBase
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     *
     * Get custom headers for API response.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Send authorized request to Fitbit API.
     *
     * @param string $url called url
     * @param string $method http method
     * @param array $data data in body
     * @return ResponseInterface response from Fitbit API
     */
    public function send($url, $method = 'GET', $data = [])
    {
        $method = strtolower($method);

        $settings = [
            'headers'  => array_merge([
                'Authorization' => 'Bearer ' . $this->getToken(),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ], $this->getHeaders()),
        ];

        if ($method == 'post') {
            $settings['form_params'] = $data;
        }

        return $this->client
            ->$method($url, $settings);
    }

}
