<?php

namespace Stanford\ExternalModuleManager;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */


try {
//$repos = $module->getClient()->api('current_user')->repositories();

    if (isset($_POST['repo']) && isset($_POST['command'])) {
        $module->testGithub(filter_var($_POST['repo'], FILTER_SANITIZE_STRING), filter_var($_POST['command'], FILTER_SANITIZE_STRING));
    }

    //$module->testGithub('external-module-manager', 'collaborators');


    ?>
    <div class="container-fluid">
        <div class="row">
            <form method="post">
                <div class="form-group">
                    <label for="exampleInputEmail1">Github Repository</label>
                    <input type="text" class="form-control" id="repo" name="repo" aria-describedby="emailHelp"
                           placeholder="Enter Github Repository Key">
                </div>
                <div class="form-group">
                    <label for="exampleInputPassword1">Github Command</label>
                    <input type="text" class="form-control" id="command" name="command"
                           placeholder="Enter Github Repository Command">
                    <small id="emailHelp" class="form-text text-muted">You can add any command from repo API after repo
                        name. for more details check: <a href="https://docs.github.com/en/rest/reference/repos"
                                                         target="_blank">Github Repo API doc</a> .</small>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
    <?php
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
    // echo 'curl -i -H "Authorization: token ' . $module->getAccessToken() . '" -H "Accept: application/vnd.github.v3+json" https://api.github.com/repos/susom/external-module-manager';

    # test secret
    echo $module->getUrl('pages/webhook.php', true, true);
    if (isset($_GET['update_commits'])) {
        $module->updateREDCapRepositoriesWithLastCommit();
    }
} catch (\Exception $e) {
    echo $e->getMessage();
}
