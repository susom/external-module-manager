<?php


namespace Stanford\ExternalModuleManager;

/**
 * Class Client
 * @package Stanford\ProjectPortal
 */
class Client
{
    private $token;

    private $jwtToken;

    private $portalUsername;

    private $portalPassword;

    private $portalBaseURL;

    private $guzzleClient;

    public function __construct($token)
    {
        $this->setToken($token);
        $this->setGuzzleClient(new \GuzzleHttp\Client());
    }


    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getGuzzleClient(): \GuzzleHttp\Client
    {
        return $this->guzzleClient;
    }

    /**
     * @param \GuzzleHttp\Client $guzzleClient
     */
    public function setGuzzleClient(\GuzzleHttp\Client $guzzleClient): void
    {
        $this->guzzleClient = $guzzleClient;
    }
}
