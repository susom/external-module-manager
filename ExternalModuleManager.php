<?php
namespace Stanford\ExternalModuleManager;

require_once "emLoggerTrait.php";

use ExternalModules\ExternalModules;





class ExternalModuleManager extends \ExternalModules\AbstractExternalModule {

    use emLoggerTrait;

    public $pta;    // PTA number on file to cover expenses (if any)



    public function __construct() {
		parent::__construct();
		// Other code to run when object is instantiated
	}


    /**
     * Only display the shortcut links based on proper user rights
     * @param $project_id
     * @param $link
     * @return false|null
     */
    public function redcap_module_link_check_display($project_id, $link) {
        if ($link['name'] == "Project Cost/Fees") {
            if (! $this->getUser()->hasDesignRights($project_id)) {
                return false;
            }
        }
        // $this->emDebug($link);
        return $link;
    }


    /**
     * Scan the specified project for external modules and update the scan results
     */
    public function scanProject($project_id) {
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
            group by rems.project_id, rem.directory_prefix", [ $project_id ]);
        $orphans=[];
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
        $this->log("ems-enabled", $payload);

        // Save to em settings
        $this->setProjectSetting('ems-enabled', $versionsByPrefix, $project_id);
        $this->setProjectSetting('ems-orphaned', $orphans, $project_id);
        $this->setProjectSetting('em-scan-date', date("Y-m-d H:i:s"));

        return $payload;
    }



    public function getExternalModuleUsage() {


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

        $resultsByModule[];
        $resultsByProject[];
        while ($row = db_fetch_assoc($q)) {
            $results[] = $row;
        }

    }


}
