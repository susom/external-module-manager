<?php


namespace Stanford\ExternalModuleManager;

/**
 * Class Client
 *
 * Plain cURL HTTP client for the cron self-calls and hub -> spoke service calls.
 *
 * Deliberately does NOT use Guzzle. REDCap core ships Guzzle, but several other EMs bundle their own
 * (older) copy and register Composer autoloaders that take precedence once those modules are
 * instantiated - e.g. REDCapEntity's EntityFactory instantiates every enabled module. That leaves core
 * and bundled Guzzle classes mixed in one request, which fatals when they disagree (REDCap 17.5.2:
 * "Call to undefined method GuzzleHttp\Psr7\Utils::asciiToUpper()").
 *
 * @package Stanford\ProjectPortal
 */
class Client
{
    const CONNECT_TIMEOUT = 30;

    const MAX_REDIRECTS = 5;

    const ERROR_BODY_MAX_LENGTH = 1000;

    private $token;

    private $jwtToken;

    private $portalUsername;

    private $portalPassword;

    private $portalBaseURL;

    public function __construct($token)
    {
        $this->setToken($token);
    }

    /**
     * @param string $url
     * @return string response body
     * @throws \RuntimeException on transport failure or non-2xx response
     */
    public function get(string $url): string
    {
        return $this->send('GET', $url, [CURLOPT_HTTPGET => true]);
    }

    /**
     * @param string $url
     * @param array $formParams sent as application/x-www-form-urlencoded
     * @param array $headers e.g. ['Accept: application/json']
     * @return string response body
     * @throws \RuntimeException on transport failure or non-2xx response
     */
    public function post(string $url, array $formParams, array $headers = []): string
    {
        return $this->send('POST', $url, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($formParams, '', '&'),
            CURLOPT_HTTPHEADER => $headers,
        ]);
    }

    private function send(string $method, string $url, array $options): string
    {
        // No overall timeout on purpose: the cron self-call waits for the whole spoke fan-out to finish.
        $ch = curl_init($url);
        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
            CURLOPT_USERAGENT => 'REDCap-ExternalModuleManager',
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($body === false) {
            throw new \RuntimeException("$method $url failed: $error");
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("$method $url returned HTTP $status: " . $this->summarizeBody($body));
        }
        return $body;
    }

    /**
     * Error pages are usually HTML (e.g. a PHP fatal), so strip tags to keep the actual message readable.
     */
    private function summarizeBody(string $body): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)));
        if (strlen($text) > self::ERROR_BODY_MAX_LENGTH) {
            $text = substr($text, 0, self::ERROR_BODY_MAX_LENGTH) . ' (truncated...)';
        }
        return $text;
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
}
