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
define('ANALYSIS_STATUS', 2);

/**
 * Class ExternalModuleManager
 * @package Stanford\ExternalModuleManager
 * @property array $externalModulesDBRecords
 * @property array $externalModulesREDCapRecords
 * @property \Project $project
 * @property \Stanford\ExternalModuleDeployment\ExternalModuleDeployment $deploymentEm
 * @property \REDCapEntity\EntityFactory $entityFactory
 * @property  array $projectEMUsage
 */
class ExternalModuleManager extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    private $externalModulesDBRecords;

    private $externalModulesREDCapRecords;

    private $project;

    private $deploymentEm;

    private $entityFactory;

    private $projectEMUsage;

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

        $types['project_external_modules_usage'] = [
            'label' => 'Project External Module Usage',
            'label_plural' => 'Projects External Module Usage',
            'icon' => 'codebook',
            'properties' => [
                'module_prefix' => [
                    'name' => 'Module Prefix',
                    'type' => 'text',
                    'required' => true,
                ],
                'project_id' => [
                    'name' => 'Project',
                    'type' => 'project',
                    'required' => true,
                ],
                'project_title' => [
                    'name' => 'Project Title',
                    'type' => 'text',
                    'required' => true,
                ],
                'status' => [
                    'name' => 'Status',
                    'type' => 'text',
                    'default' => '0',
                    'choices' => [
                        '0' => 'Development',
                        '1' => 'Production',
                        '2' => 'Analysis',
                    ],
                    'required' => true,
                ],
                'record_count' => [
                    'name' => 'Number of Records',
                    'type' => 'integer',
                    'required' => true,
                ],
                'is_em_enabled' => [
                    'name' => 'Is EM Enabled?',
                    'type' => 'boolean',
                    'required' => true,
                ],
                'number_of_settings_rows' => [
                    'name' => 'Number of Settings Rows',
                    'type' => 'integer',
                    'required' => true,
                ],
            ],
            'special_keys' => [
                'label' => 'number', // "number" represents the entity label.
                #'project' => 'project_id', // "project_id" represents the project which the entity belongs to.
            ],
        ];


        return $types;
    }


    public function getEMTotalNumberOfProjects($externalModuleId, $status)
    {
        $q = $this->query("select count(*) as count from redcap_projects where project_id IN (select project_id from redcap_external_module_settings where external_module_id = ? and `key` = 'enabled' and `value`= 'true') and status = ?", [$externalModuleId, $status]);

        $row = db_fetch_assoc($q);
        return $row['count'];
    }

    /**
     * @return array
     *
     */
    public function getExternalModulesDBRecords()
    {
        if ($this->externalModulesDBRecords) {
            return $this->externalModulesDBRecords;
        } else {
            $this->setExternalModulesDBRecords();
            return $this->externalModulesDBRecords;
        }
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
                    if ($row['module_prefix'] != '') {
                        $this->createExternalModuleREDCapRecord($row['external_module_id'], $row['module_prefix']);
                    }
                }
                $total_enabled_dev_projects = $this->getEMTotalNumberOfProjects($row['external_module_id'], DEVELOPMENT_STATUS);
                $total_enabled_prod_projects = $this->getEMTotalNumberOfProjects($row['external_module_id'], PRODUCTION_STATUS);
                $em['redcap'] = $this->getExternalModulesREDCapRecords()[$row['module_prefix']];

                // use this to update redcap record.
                $em['redcap'][$this->getFirstEventId()]['count_active_production'] = $total_enabled_prod_projects;
                $em['redcap'][$this->getFirstEventId()]['count_active_dev'] = $total_enabled_dev_projects;
                $em['redcap'][$this->getFirstEventId()]['globally_enabled'] = $this->isEMGloballyEnabled($row['external_module_id']);
                $em['redcap'][$this->getFirstEventId()]['discoverable_in_project'] = $this->isEMDiscoverableInProject($row['external_module_id']);


                $em['entity'] = array(
                    'module_prefix' => $row['module_prefix'],
                    'version' => $this->getExternalModuleDeployedVersion($row['external_module_id']),
                    //'date' => date('Y-m-d H:i:s'),
                    'date' => time(),
                    'total_enabled_projects' => $row['total_enabled_projects'],
                    'globally_enabled' => $this->isEMGloballyEnabled($row['external_module_id']),
                    'total_enabled_dev_projects' => $total_enabled_dev_projects,
                    'total_enabled_prod_projects' => $total_enabled_prod_projects,
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
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * @param $externalModuleId
     * @return bool
     */
    public function isEMDiscoverableInProject($externalModuleId)
    {
        $q = $this->query("select `value` from redcap_external_module_settings where project_id is NULL and `key` = 'discoverable-in-project' and external_module_id = ?", [$externalModuleId]);

        $row = db_fetch_assoc($q);
        if ($row['value']) {
            return $row['value'] ? 1 : 0;
        } else {
            return 0;
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
        //$data = array('test_project_pid' => $this->getExternalModuleSampleProjectId($externalModuleId));

        if (in_array($fullExternalModuleName, $folders)) {
            $path = $this->getFolderPath($fullExternalModuleName);
            if (is_dir($path . '/.git')) {
                $content = explode("\n\t", file_get_contents($path . '/.git/config'));
                // url
                $matches = preg_grep('/^url/m', $content);
                $url = end($matches);
                $data['git_url'] = preg_replace('/^url\s=\s/m', '', $url);
                // $data[GITHUB_REPO_OPTION] = true;
            } elseif (file_exists($path . '/.gitrepo')) {
                $content = file_get_contents($path . '/.gitrepo');
                $parts = explode("\n\t", $content);
                $matches = preg_grep('/^remote?/m', $parts);
                $url = end($matches);
                $data['git_url'] = preg_replace('/^remote\s=\s/m', '', $url);
                // $data[GITHUB_REPO_OPTION] = true;
                $matches = preg_grep('/^commit?/m', $parts);
                $commit = explode(" ", end($matches));
                //$data['current_git_commit'] = end($commit);
            }
        }
        return $data;
    }

    /**
     * @param int $externalModuleId
     * @param string $prefix
     * @return bool
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createExternalModuleREDCapRecord($externalModuleId, $prefix)
    {
        $data = $this->lookupExternalModuleGithubInformation($externalModuleId, $prefix);
        $data[REDCap::getRecordIdField()] = $prefix;
        $data['redcap_event_name'] = $this->getProject()->getUniqueEventNames($this->getFirstEventId());
        $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
        if (empty($response['errors'])) {
            $key = Repository::getGithubKey($data['git_url']);
            //$this->getDeploymentEm()->updateRepositoryDefaultBranchLatestCommit($key, $prefix);
            return true;
        } else {
            $this->emError($response);
            throw new \Exception("cant save information for EM : " . $prefix . ' em id is: ' . $externalModuleId);
        }
    }


    /**
     * @param $record
     * @return false|mixed|null
     */
    public function isProjectEMUsageRecordExist($record)
    {
        $entity = $this->getEntityFactory()->query('project_external_modules_usage')
            ->condition('module_prefix', $record['module_prefix'])
            ->condition('project_id', $record['project_id'])
            ->execute();
        if ($entity) {
            return array_pop($entity);
        }
        return false;
    }

    public function createProjectsExternalModuleUsageLogs()
    {
        try {
            if ($this->getProjectEMUsage()) {
                foreach ($this->getProjectEMUsage() as $record) {
                    if (!$entity = $this->isProjectEMUsageRecordExist($record)) {
                        $entity = $this->getEntityFactory()->create('project_external_modules_usage', $record['entity']);
                        echo $entity->getId() . '<br>';
                    } else {
                        #$entity = $this->getEntityFactory()->getInstance('project_external_modules_usage', $recordId);
                        if ($entity->setData($record['entity'])) {
                            $entity->save();
                        } else {
                            // Get a list of properties that failed on update
                            print_r($entity->getErrors());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateEMREDCapRecordUsageNumbers($record, $eventId): bool
    {
        $data[REDCap::getRecordIdField()] = $record[$this->getFirstEventId()][REDCap::getRecordIdField()];
        $data['count_active_production'] = $record[$this->getFirstEventId()]['count_active_production'];
        $data['count_active_dev'] = $record[$this->getFirstEventId()]['count_active_dev'];
        $data['deploy_setup___1'] = $record[$this->getFirstEventId()]['globally_enabled'] ?: false;
        $data['deploy_setup___2'] = $record[$this->getFirstEventId()]['discoverable_in_project'] ?: false;
        $data['redcap_event_name'] = $this->getProject()->getUniqueEventNames($eventId ?: $this->getFirstEventId());
        $response = \REDCap::saveData($this->getProjectId(), 'json', json_encode(array($data)));
        if (empty($response['errors'])) {
            return true;
        } else {
            throw new \Exception("cant save information for EM : " . $data['module_name']);
        }
    }

    public function createExternalModuleUtilizationLogs($eventId)
    {
        try {
            if ($this->getExternalModulesDBRecords()) {
                foreach ($this->getExternalModulesDBRecords() as $record) {
                    $entity = $this->getEntityFactory()->create('external_modules_utilization', $record['entity']);
                    $this->updateEMREDCapRecordUsageNumbers($record['redcap'], $eventId);
                    echo $entity->getId() . '<br>';
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

    /**
     * @return array
     */
    public function getProjectEMUsage(): array
    {
        if (!$this->projectEMUsage) {
            $this->setProjectEMUsage();
        }
        return $this->projectEMUsage;
    }

    /**
     * @param array $projectEMUsage
     */
    public function setProjectEMUsage(): void
    {
        try {
            $projectEMUsage = array();
            $q = $this->query("select
                       rems.external_module_id,
                       rem.directory_prefix as module_prefix,
                       rems.project_id,
                       rp.app_title as project_title,
                       rp.status,
                       rrc.record_count,
                       sum(case when rems.`key` = 'enabled' and rems.value = 'true' then 1 else 0 end) as is_em_enabled,
                       count(*) as number_of_settings_rows
                    from redcap_external_module_settings rems
                    join redcap_external_modules rem on rems.external_module_id = rem.external_module_id
                    join redcap_projects rp on rems.project_id = rp.project_id
                    join redcap_record_counts rrc on rp.project_id = rrc.project_id
                    where rems.project_id is not null
                    group by rems.external_module_id, rems.project_id, rem.directory_prefix, rp.app_title, rp.status, rrc.record_count ", []);

            while ($row = db_fetch_assoc($q)) {
                $em = $row;

                $em['entity'] = array(
                    'module_prefix' => $row['module_prefix'],
                    'project_id' => $row['project_id'],
                    'project_title' => $row['project_title'],
                    'status' => $row['status'],
                    'record_count' => $row['record_count'],
                    'is_em_enabled' => $row['is_em_enabled'],
                    'number_of_settings_rows' => $row['number_of_settings_rows'],
                );
                $projectEMUsage[] = $em;
            }
            $this->projectEMUsage = $projectEMUsage;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

    }


}
