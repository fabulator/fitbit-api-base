<?php
namespace Fabulator;

use \GuzzleHttp\Client;
use \Psr\Http\Message\ResponseInterface;

class FitbitAPIBase
{

    private $fitbitAPIUrl = 'https://api.fitbit.com/';
    private $token;
    private $headers = [];

    /**
     * Fitbit constructor.
     * @param string $clientId your client id
     * @param string $secret your secret key
     */
    public function __construct(string $clientId, string $secret)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
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
        string $redirectUri,
        array $scope,
        string $responseType = 'code',
        int $expiresIn = null,
        string $prompt = 'none',
        string $state = null): string
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

        return 'https://www.fitbit.com/oauth2/authorize?' . http_build_query($parameters);
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
    public function requestAccessToken(string $code, string $redirectUri, int $expiresIn = null, string $state = null): ResponseInterface
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
    public function refreshAccessToken(string $refreshToken, int $expiresIn = null): ResponseInterface
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
    public function revokeAccessToken(string $token): ResponseInterface
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
    private function tokenRequest(string $namespace, array $parameters): ResponseInterface
    {
        $client = new Client([
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '. base64_encode($this->clientId . ':' . $this->secret)
            ]
        ]);

        return $client->post($this->fitbitAPIUrl . $namespace . '?' . http_build_query($parameters));
    }

    /**
     * Set Fitbit token
     *
     * @param string $token token to set
     * @return FitbitAPI
     */
    public function setToken(string $token): FitbitAPI
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get Fitbit token.
     *
     * @return string token
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set custom headers for API requests.
     *
     * @param array $headers
     * @return FitbitAPI
     */
    public function setHeaders(array $headers): FitbitAPI
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
    public function getHeaders(): array
    {
        return $this->headers;
    }


    /**
     * Send GET request to Fitbit API.
     *
     * @param string $namespace called namespace
     * @param string $user Fitbit user
     * @param string $file format of response
     * @return ResponseInterface response from Fitbit API
     */
    public function get(string $namespace, string $user = '-', string $file = '.json'): ResponseInterface
    {
        return $this->send($namespace, 'GET', [], $user, $file);
    }

    /**
     * Send POST request to Fitbit API.
     *
     * @param string $namespace called namespace
     * @param array $data data in body to send
     * @param string $user Fitbit user
     * @return ResponseInterface response from Fitbit API
     */
    public function post(string $namespace, array $data, string $user = '-'): ResponseInterface
    {
        return $this->send($namespace, 'POST', $data, $user);
    }

    /**
     * Send DELETE request to Fitbit API.
     *
     * @param string $namespace called namespace
     * @param string $user Fitbit user
     * @return ResponseInterface response from Fitbit API
     */
    public function delete(string $namespace, string $user = ''): ResponseInterface
    {
        return $this->send($namespace, 'DELETE', [], $user);
    }

    /**
     * Send authorized request to Fitbit API.
     *
     * @param string $namespace called namespace
     * @param string $method http method
     * @param array $data data in body
     * @param string $user Fitbit user
     * @param string $file type of requested format
     * @return ResponseInterface response from Fitbit API
     */
    public function send(string $namespace, string $method = 'GET', array $data = [], string $user = '-', string $file = '.json'): ResponseInterface
    {
        $client = new Client();

        $method = strtolower($method);

        $settings = [
            'headers'  => array_merge([
                'Authorization' => 'Bearer ' . $this->getToken(),
            ], $this->getHeaders()),
        ];

        if ($method == 'post') {
            $settings['body'] = $data;
        }

        return $client
            ->$method($this->fitbitAPIUrl . '1/' . ($user ? 'user/' . $user . '/' : '') . $namespace . $file, $settings);
    }

}
