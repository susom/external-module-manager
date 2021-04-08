<?php

namespace Stanford\ExternalModuleManager;

// This file is generated by Composer
require_once __DIR__ . '/vendor/autoload.php';
require_once "emLoggerTrait.php";
require_once "classes/User.php";
require_once "classes/Repository.php";

use ExternalModules\ExternalModules;
use Stanford\ExternalModuleManager\User;
use Stanford\ExternalModuleManager\Repository;
use REDCap;
use Project;
use \Firebase\JWT\JWT;

/**
 * Class ExternalModuleManager
 * @package Stanford\ExternalModuleManager
 * @property string $pta
 * @property User $user
 * @property Repository $repository
 * @property array $repositories
 * @property Project $project
 * @property string $jwt
 * @property string $accessToken
 * @property \GuzzleHttp\Client $guzzleClient
 * @property array $redcapRepositories
 */
class ExternalModuleManager extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    private $pta;    // PTA number on file to cover expenses (if any)

    private $user;

    private $repositories;

    private $repository;

    private $project;

    private $jwt;

    private $accessToken;

    private $guzzleClient;

    private $redcapRepositories;

    public function __construct()
    {
        parent::__construct();
        // Other code to run when object is instantiated

        if (isset($_GET['pid'])) {
            $this->setProject(new Project(filter_var($_GET['pid'], FILTER_SANITIZE_STRING)));

            if (!defined('NOAUTH') || NOAUTH == false) {
                // get user right then set the user.
                $right = REDCap::getUserRights();
                $user = $right[USERID];
                $this->setUser(new User($user));
            }

            // set repositories
            $this->setRepositories();

            // initiate guzzle client to get access token
            $this->setGuzzleClient(new \GuzzleHttp\Client());

            //authenticate github client
            // no longer needed we will do all calls manually package is not fully functional
            //$this->getClient()->authenticate($this->getAccessToken(), null, \Github\Client::AUTH_ACCESS_TOKEN);

            // set EM records saved in REDCap
            $this->setRedcapRepositories();
        }
    }

    public function updateREDCapRepositoryWithLastCommit($payload)
    {
        foreach ($this->getRedcapRepositories() as $recordId => $repository) {
            $key = Repository::getGithubKey($repository[$this->getFirstEventId()]['git_url']);
            // TODO probably we can add another check for before commit and compare it with whatever in redcap
            if ($key == $payload['repository']['name']) {
                $data[REDCap::getRecordIdField()] = $recordId;
                $data['current_git_commit'] = $payload['after'];
                $data['date_of_latest_commit'] = $payload['commits'][0]['timestamp'];
                $data['redcap_event_name'] = $this->getProject()->getUniqueEventNames($this->getFirstEventId());
                $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
                if (empty($response['errors'])) {
                    $this->emLog("webhook triggered for EM $key last commit hash: " . $payload['after']);
                    die();
                } else {
                    throw new \Exception("cant update last commit for EM : " . $repository[$this->getFirstEventId()]['module_name']);
                }
            }
        }
    }

    public function updateREDCapRepositoriesWithLastCommit()
    {
        foreach ($this->getRedcapRepositories() as $recordId => $repository) {
            if ($repository[$this->getFirstEventId()]['git_url']) {
                $key = Repository::getGithubKey($repository[$this->getFirstEventId()]['git_url']);
                $commit = $this->getRepositoryLastCommit($key);
                $data[REDCap::getRecordIdField()] = $recordId;
                $data['current_git_commit'] = $commit->sha;
                $data['date_of_latest_commit'] = $commit->commit->author->date;
                $data['redcap_event_name'] = $this->getProject()->getUniqueEventNames($this->getFirstEventId());
                $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
                if (empty($response['errors'])) {

                    $commit->files = array();

                    echo '<pre>';
                    print_r($commit);
                    echo '</pre>';
                } else {
                    throw new \Exception("cant update last commit for EM : " . $repository[$this->getFirstEventId()]['module_name']);
                }
            }

        }
    }

    /**
     * this function will check HMAC header verify the request is valid. test
     * @throws \Exception test
     */
    public function verifyWebhookSecret()
    {
        list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
//        $this->emLog("************************************************************************************************************************************");
//        $this->emLog($algo);
        $rawPost = trim(file_get_contents('php://input'));
        $secret = $this->getProjectSetting('github-webhook-secret');
//        $this->emLog("secret    " . $secret);
//        $this->emLog("hash      " . $hash);
//        $this->emLog("hash_hmac " . hash_hmac($algo, $rawPost, $secret));
//        $this->emLog(hash_equals($hash, hash_hmac($algo, $rawPost, $secret)));
        // $this->emLog($rawPost);
        if (!hash_equals($hash, hash_hmac($algo, $rawPost, $secret))) {
            throw new \Exception('Hook secret does not match.');
        }
    }

    public function getRepositoryLastCommit($key)
    {
        try {
            $response = $this->getGuzzleClient()->get('https://api.github.com/repos/susom/' . $key . '/commits', [
                'headers' => [
                    'Authorization' => 'token ' . $this->getAccessToken(),
                    'Accept' => 'application/vnd.github.v3+json'
                ]
            ]);
            $commits = json_decode($response->getBody());
            //return first commit in the array which is the last one.
            return $commits[0];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->emError("Exception pulling last commit for $key: " . $e->getMessage());
        }
    }

    /**
     * Only display the shortcut links based on proper user rights
     * @param $project_id
     * @param $link
     * @return false|null
     */
    public function redcap_module_link_check_display($project_id, $link)
    {
        if ($link['name'] == "Project Cost/Fees") {
            if (!$this->getUser()->hasDesignRights()) {
                return false;
            }
        }
        // $this->emDebug($link);
        return $link;
    }


    /**
     * Scan the specified project for external modules and update the scan results
     */
    public function scanProject($project_id)
    {
        // Get all external modules enabled for the project
        $versionsByPrefix = ExternalModules::getEnabledModules($project_id);
        $this->emDebug($versionsByPrefix);

        // Look for any EM settings that are not active (i.e. Orphaned settings)
        $q = $this->query("select
                rem.directory_prefix,
                rems.project_id,
                count(*) as count
            from redcap_external_module_settings rems
            join redcap_external_modules rem on rems.external_module_id = rem.external_module_id
            where rems.project_id = ?
            group by rems.project_id, rem.directory_prefix", [$project_id]);
        $orphans = [];
        while ($row = db_fetch_assoc($q)) {
            $prefix = $row['directory_prefix'];
            $count = $row['count'];
            if (!isset($versionsByPrefix[$prefix])) {
                $orphans[$prefix] = $count;
            }
        }
        $this->emDebug($orphans);

        // Save settings to logs for scan
        $payload = [
            'project_id' => $project_id,
            'ems-enabled' => json_encode($versionsByPrefix),
            'ems-orphaned' => json_encode($orphans)
        ];
        $this->emLog("ems-enabled", $payload);

        // Save to em settings
        $this->setProjectSetting('ems-enabled', $versionsByPrefix, $project_id);
        $this->setProjectSetting('ems-orphaned', $orphans, $project_id);
        $this->setProjectSetting('em-scan-date', date("Y-m-d H:i:s"));

        return $payload;
    }

    public function testGithub($key, $command = '')
    {
        $response = $this->getGuzzleClient()->get('https://api.github.com/repos/susom/' . $key . ($command ? '/' . $command : ''), [
            'headers' => [
                'Authorization' => 'token ' . $this->getAccessToken(),
                'Accept' => 'application/vnd.github.v3+json'
            ]
        ]);
        $body = json_decode($response->getBody());
        echo '<pre>';
        print_r($body);
        echo '</pre>';
    }


    public function getExternalModuleUsage()
    {


        $q = $this->query("select
                rems.external_module_id,
                rem.directory_prefix,
                rems.project_id,
                rp.app_title,
                rp.status,
                rrc.record_count,
                sum(case when rems.`key` = 'enabled' and rems.value = 'true' then 1 else 0 end) as is_enabled,
                count(*) as settings
            from redcap_external_module_settings rems
            join redcap_external_modules rem on rems.external_module_id = rem.external_module_id
            join redcap_projects rp on rems.project_id = rp.project_id
            join redcap_record_counts rrc on rp.project_id = rrc.project_id
            where rems.project_id is not null
            group by rems.external_module_id, rems.project_id, rem.directory_prefix, rp.app_title, rp.status, rrc.record_count", []
        );

        $resultsByModule = [];
        $resultsByProject = [];
        while ($row = db_fetch_assoc($q)) {
            $results[] = $row;
        }

    }

    /**
     * @return string
     */
    public function getPta(): string
    {
        return $this->pta;
    }

    /**
     * @param string $pta
     */
    public function setPta(string $pta): void
    {
        $this->pta = $pta;
    }

    /**
     * @return \Stanford\ExternalModuleManager\User
     */
    public function getUser(): \Stanford\ExternalModuleManager\User
    {
        return $this->user;
    }

    /**
     * @param \Stanford\ExternalModuleManager\User $user
     */
    public function setUser(\Stanford\ExternalModuleManager\User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return \Stanford\ExternalModuleManager\Repository
     */
    public function getRepository(): \Stanford\ExternalModuleManager\Repository
    {
        return $this->repository;
    }

    /**
     * @param \Stanford\ExternalModuleManager\Repository $repository
     */
    public function setRepository(\Stanford\ExternalModuleManager\Repository $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * @return array
     */
    public function getRepositories(): array
    {
        if ($this->repositories) {
            return $this->repositories;
        } else {
            $this->setRepositories();
            return $this->repositories;
        }

    }

    /**
     * @param array $repositories
     */
    public function setRepositories(): void
    {
        $q = $this->query("select
                rems.external_module_id,
                rem.directory_prefix,
                rems.project_id,
                rp.app_title,
                rp.status,
                rrc.record_count,
                sum(case when rems.`key` = 'enabled' and rems.value = 'true' then 1 else 0 end) as is_enabled,
                count(*) as settings
            from redcap_external_module_settings rems
            join redcap_external_modules rem on rems.external_module_id = rem.external_module_id
            join redcap_projects rp on rems.project_id = rp.project_id
            join redcap_record_counts rrc on rp.project_id = rrc.project_id
            where rems.project_id is not null
            group by rems.external_module_id, rems.project_id, rem.directory_prefix, rp.app_title, rp.status, rrc.record_count", []
        );

        $resultsByModule = [];
        $resultsByProject = [];
        $repositories = [];
        while ($row = db_fetch_assoc($q)) {
            $repositories[] = new Repository($row);
        }
        $this->repositories = $repositories;
    }

    /**
     * @return \Project
     */
    public function getProject(): \Project
    {
        return $this->project;
    }

    /**
     * @param \Project $project
     */
    public function setProject(\Project $project): void
    {
        $this->project = $project;
    }

    /**
     * @return string
     */
    public function getJwt(): string
    {
        if ($this->jwt) {
            return $this->jwt;
        } else {
            $this->setJwt();
            return $this->jwt;
        }

    }

    /**
     * @param string $jwt
     */
    public function setJwt(): void
    {
        $payload = array(
            "iss" => "108296",
            "iat" => time() - 60,
            "exp" => time() + 360
        );
        $privateKey = $this->getProjectSetting('github-app-private-key');
        $jwt = JWT::encode($payload, $privateKey, 'RS256');
        $this->jwt = $jwt;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        } else {
            $this->setAccessToken();
            return $this->accessToken;
        }

    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(): void
    {
        $response = $this->getGuzzleClient()->post('https://api.github.com/app/installations/' . $this->getProjectSetting('github-installation-id') . '/access_tokens', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getJwt(),
                'Accept' => 'application/vnd.github.v3+json'
            ]
        ]);
        $body = json_decode($response->getBody());
        $this->accessToken = $body->token;
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

    /**
     * @return array
     */
    public function getRedcapRepositories(): array
    {
        if ($this->redcapRepositories) {
            return $this->redcapRepositories;
        } else {
            $this->setRedcapRepositories();
            return $this->redcapRepositories;
        }

    }

    /**
     * @param array $redcapRepositories
     */
    public function setRedcapRepositories(): void
    {
        $param = array(
            'project_id' => $this->getProjectId(),
            'return_format' => 'array',
            'events' => $this->getFirstEventId()
        );
        $data = REDCap::getData($param);

        $this->redcapRepositories = $data;
    }


}
