<?php

namespace Stanford\ExternalModuleManager;

use \REDCap;
use Stanford\ExternalModuleDeployment;
use \REDCapEntity\EntityFactory;

// This file is generated by Composer
require_once __DIR__ . '/vendor/autoload.php';
require_once "emLoggerTrait.php";
require_once "classes/User.php";
require_once "classes/Repository.php";


define('GITHUB_REPO_OPTION', 'module_source___1');
define('DEVELOPMENT_STATUS', 0);
define('PRODUCTION_STATUS', 1);
define('ANaLYSIS_STATUS', 2);

/**
 * Class ExternalModuleManager
 * @package Stanford\ExternalModuleManager
 * @property array $externalModulesDBRecords
 * @property array $externalModulesREDCapRecords
 * @property \Project $project
 * @property \Stanford\ExternalModuleDeployment\ExternalModuleDeployment $deploymentEm
 * @property \REDCapEntity\EntityFactory $entityFactory
 */
class ExternalModuleManager extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    private $externalModulesDBRecords;

    private $externalModulesREDCapRecords;

    private $project;

    private $deploymentEm;

    private $entityFactory;

    public function __construct()
    {
        parent::__construct();
        // Other code to run when object is instantiated

        if (isset($_GET['pid'])) {
            $this->setProject(new \Project(filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT)));

            if ($this->getProjectSetting('external-module-deployment')) {
                $this->setDeploymentEm(\ExternalModules\ExternalModules::getModuleInstance($this->getProjectSetting('external-module-deployment')));

                $this->setEntityFactory(new \REDCapEntity\EntityFactory());
            }
        }
    }


    public function redcap_entity_types()
    {
        $types = [];

        $types['external_modules_utilization'] = [
            'label' => 'External Modules Utilization',
            'label_plural' => 'External Modules Utilization',
            'icon' => 'home_pencil',
            'properties' => [
                'module_prefix' => [
                    'name' => 'Module Prefix',
                    'type' => 'text',
                    'required' => true,
                ],
                'version' => [
                    'name' => 'Version',
                    'type' => 'text',
                    'required' => false,
                ],
                'date' => [
                    'name' => 'Date Added',
                    'type' => 'date',
                    'required' => true,
                ],
                'globally_enabled' => [
                    'name' => 'Is it Globally Enabled?',
                    'type' => 'boolean',
                    'required' => true,
                ],
                'total_enabled_projects' => [
                    'name' => 'Total Number of Projects enabled this EM?',
                    'type' => 'integer',
                    'default' => '0',
                    'required' => true,
                ],
                'total_enabled_dev_projects' => [
                    'name' => 'Total Number of Projects in Dev Mode enabled this EM?',
                    'type' => 'integer',
                    'default' => '0',
                    'required' => true,
                ],
                'total_enabled_prod_projects' => [
                    'name' => 'Total Number of Projects in Production Mode enabled this EM?',
                    'type' => 'integer',
                    'default' => '',
                    'required' => true,
                ],
                'total_using_projects' => [
                    'name' => 'Total Number of Projects actually using the EM?',
                    'type' => 'text',
                    'required' => false,
                ],
            ],
            'special_keys' => [
                'label' => 'module_prefix', // "name" represents the entity label.
            ],
        ];

        return $types;
    }

    /**
     * @return array
     */
    public function getExternalModulesDBRecords(): array
    {
        if ($this->externalModulesDBRecords) {
            return $this->externalModulesDBRecords;
        } else {
            $this->setExternalModulesDBRecords();
            return $this->externalModulesDBRecords;
        }
    }

    public function getEMTotalNumberOfProjects($externalModuleId, $status)
    {
        $q = $this->query("select count(*) as count from redcap_projects where project_id IN (select project_id from redcap_external_module_settings where external_module_id = ?) and status = ?", [$externalModuleId, $status]);

        $row = db_fetch_assoc($q);
        return $row['count'];
    }

    /**
     * @param array $externalModulesDBRecords
     */
    public function setExternalModulesDBRecords(): void
    {
        try {
            $externalModulesDBRecords = array();
            $q = $this->query("select rem.external_module_id,rem.directory_prefix as module_prefix,   COUNT(DISTINCT rems.project_id) total_enabled_projects
from redcap_external_module_settings rems
         JOIN redcap_external_modules rem on rem.external_module_id = rems.external_module_id
GROUP BY rems.external_module_id ", []);

            while ($row = db_fetch_assoc($q)) {
                $em = $row;
                if (!$this->getExternalModulesREDCapRecords()[$row['module_prefix']]) {
                    $this->createExternalModuleREDCapRecord($row['external_module_id'], $row['module_prefix']);
                }
                $em['redcap'] = $this->getExternalModulesREDCapRecords()[$row['module_prefix']];
                $em['entity'] = array(
                    'module_prefix' => $row['module_prefix'],
                    'version' => $this->getExternalModuleDeployedVersion($row['external_module_id']),
                    'date' => time(),
                    'total_enabled_projects' => $row['total_enabled_projects'],
                    'globally_enabled' => $this->isEMGloballyEnabled($row['external_module_id']),
                    'total_enabled_dev_projects' => $this->getEMTotalNumberOfProjects($row['external_module_id'], DEVELOPMENT_STATUS),
                    'total_enabled_prod_projects' => $this->getEMTotalNumberOfProjects($row['external_module_id'], PRODUCTION_STATUS),
                    'total_using_projects' => '' // TODO,
                );
                $externalModulesDBRecords[] = $em;
            }
            $this->externalModulesDBRecords = $externalModulesDBRecords;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $externalModuleId
     * @return bool
     */
    public function isEMGloballyEnabled($externalModuleId)
    {
        $q = $this->query("select count(*) as count  from redcap_external_module_settings where project_id is NULL and `key` = 'enabled' and external_module_id = ?", [$externalModuleId]);

        $row = db_fetch_assoc($q);
        if ($row['count'] && $row['count'] > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $externalModuleId
     * @return false|mixed|string
     */
    private function getExternalModuleDeployedVersion($externalModuleId)
    {
        $q = $this->query("select `value` from redcap_external_module_settings where `key` = 'version' and external_module_id = ?", [$externalModuleId]);

        $row = db_fetch_assoc($q);
        if ($row['value'] && $row['value'] != '') {
            return $row['value'];
        } else {
            return '';
        }
    }

    /**
     * @param string $folder
     * @return false|string
     */
    public function getFolderPath($folder)
    {
        $arr = explode("/", __DIR__);
        $parts = array_slice($arr, -2, 2, true);
        if (is_dir(implode("/", $parts) . '/../' . $folder)) {
            return implode("/", $parts) . '/../' . $folder;
        } elseif (is_dir('../' . $folder)) {
            return '../' . $folder;
        } elseif (is_dir(__DIR__ . '/../' . $folder)) {
            return __DIR__ . '/../' . $folder;
        }
        return false;
    }

    /**
     * @param int $externalModuleId
     * @return false|mixed|string
     */
    private function getExternalModuleSampleProjectId($externalModuleId)
    {
        $q = $this->query("select project_id from redcap_external_module_settings where project_id is not NULL and external_module_id = ? LIMIT 1,1", [$externalModuleId]);

        $row = db_fetch_assoc($q);
        if ($row['project_id'] && $row['project_id'] != '') {
            return $row['project_id'];
        } else {
            return false;
        }
    }

    /**
     * @param int $externalModuleId
     * @param string $prefix
     * @return array
     */
    private function lookupExternalModuleGithubInformation($externalModuleId, $prefix)
    {
        $version = $this->getExternalModuleDeployedVersion($externalModuleId);
        $fullExternalModuleName = $prefix . '_' . $version;
        $folders = scandir(__DIR__ . '/../');
        $data = array('test_project_pid' => $this->getExternalModuleSampleProjectId($externalModuleId));

        if (in_array($fullExternalModuleName, $folders)) {
            $path = $this->getFolderPath($fullExternalModuleName);
            if (is_dir($path . '/.git')) {
                $content = explode("\n\t", file_get_contents($path . '/.git/config'));
                // url
                $matches = preg_grep('/^url/m', $content);
                $url = end($matches);
                $data['git_url'] = preg_replace('/^url\s=\s/m', '', $url);
                $data[GITHUB_REPO_OPTION] = true;
            } elseif (file_exists($path . '/.gitrepo')) {
                $content = file_get_contents($path . '/.gitrepo');
                $parts = explode("\n\t", $content);
                $matches = preg_grep('/^remote?/m', $parts);
                $url = end($matches);
                $data['git_url'] = preg_replace('/^remote\s=\s/m', '', $url);
                $data[GITHUB_REPO_OPTION] = true;
                $matches = preg_grep('/^commit?/m', $parts);
                $commit = explode(" ", end($matches));
                $data['current_git_commit'] = end($commit);
            }
        }
        return $data;
    }

    /**
     * @param int $externalModuleId
     * @param string $prefix
     * @return bool
     * @throws \Exception
     */
    public function createExternalModuleREDCapRecord($externalModuleId, $prefix)
    {
        $data = $this->lookupExternalModuleGithubInformation($externalModuleId, $prefix);
        $data[REDCap::getRecordIdField()] = $prefix;
        $data['redcap_event_name'] = $this->getProject()->getUniqueEventNames($this->getFirstEventId());
        $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
        if (empty($response['errors'])) {
            $key = Repository::getGithubKey($data['git_url']);
            $this->getDeploymentEm()->updateRepositoryDefaultBranchLatestCommit($key, $prefix);
            return true;
        } else {
            throw new \Exception("cant save information for EM : " . $prefix);
        }
    }


    public function createExternalModuleUtilizationLogs()
    {
        try {
            if ($this->getExternalModulesDBRecords()) {
                foreach ($this->getExternalModulesDBRecords() as $record) {
                    $entity = $this->getEntityFactory()->create('external_modules_utilization', $record['entity']);
                    echo $entity->getData() . '<br>';
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @return array
     */
    public function getExternalModulesREDCapRecords(): array
    {
        if ($this->externalModulesREDCapRecords) {
            return $this->externalModulesREDCapRecords;
        } else {
            $this->setExternalModulesREDCapRecords();
            return $this->externalModulesREDCapRecords;
        }
    }

    /**
     * @param array $externalModulesREDCapRecords
     */
    public function setExternalModulesREDCapRecords(): void
    {
        $param = array(
            'project_id' => $this->getProjectId(),
            'return_format' => 'array',
            'events' => $this->getFirstEventId()
        );
        $externalModulesREDCapRecords = REDCap::getData($param);
        $this->externalModulesREDCapRecords = $externalModulesREDCapRecords;
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
     * @return ExternalModuleDeployment\ExternalModuleDeployment
     */
    public function getDeploymentEm(): ExternalModuleDeployment\ExternalModuleDeployment
    {
        return $this->deploymentEm;
    }

    /**
     * @param ExternalModuleDeployment\ExternalModuleDeployment $deploymentEm
     */
    public function setDeploymentEm(ExternalModuleDeployment\ExternalModuleDeployment $deploymentEm): void
    {
        $this->deploymentEm = $deploymentEm;
    }

    /**
     * @return \REDCapEntity\EntityFactory
     */
    public function getEntityFactory(): \REDCapEntity\EntityFactory
    {
        return $this->entityFactory;
    }

    /**
     * @param \REDCapEntity\EntityFactory $entityFactory
     */
    public function setEntityFactory(\REDCapEntity\EntityFactory $entityFactory): void
    {
        $this->entityFactory = $entityFactory;
    }


}
