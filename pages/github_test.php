<?php

namespace Stanford\ExternalModuleManager;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */


try {
//$repos = $module->getClient()->api('current_user')->repositories();

    echo '<pre>';
    //print_r($module->updateREDCapRepositoriesWithLastCommit());
    echo '</pre>';


    $module->testGithub('external-module-manager');


//echo '<br>';
//echo '<br>';
//echo '<br>';
//
//echo 'curl -i -H "Authorization: Bearer ' . $module->getJwt() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/app';
//
//echo '<br>';
//echo '<br>';
//echo '<br>';
//echo 'curl -i -X POST -H "Authorization: Bearer ' . $module->getJwt() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/app/installations/15898575/access_tokens';


    echo '<br>';
    echo '<br>';
    echo '<br>';
    echo 'curl -i -H "Authorization: token ' . $module->getAccessToken() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/susom/external-module-manager';

} catch (\Exception $e) {
    echo $e->getMessage();
}
