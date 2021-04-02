<?php

namespace Stanford\ExternalModuleManager;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */


//try{
$repos = $module->getClient()->api('repo')->all();

echo '<pre>';
print_r($repos);
echo '</pre>';

echo '<br>';
echo '<br>';
echo '<br>';

echo 'curl -i -H "Authorization: Bearer ' . $module->getJwt() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/app';

echo '<br>';
echo '<br>';
echo '<br>';
echo 'curl -i -X POST -H "Authorization: Bearer ' . $module->getJwt() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/app/installations/15898575/access_tokens';


echo '<br>';
echo '<br>';
echo '<br>';
echo 'curl -i -H "Authorization: token ' . $module->getAccessToken() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/installation/repositories';

//}
//catch (\Exception $e){
//    echo $e->getMessage();
//}
