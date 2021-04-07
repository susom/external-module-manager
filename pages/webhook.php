<?php

namespace Stanford\ExternalModuleManager;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */


try {
    list($algo, $hash) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
    $rawPost = file_get_contents('php://input');
    $xx = 'RYL8udHvqGcbkjXk8sPXi3zGhCQGyHqGa7utjPgG';
    $yourHash = base64_encode(hash_hmac('sha1', $_POST['payload'], $xx));
    if (!hash_equals($hash, hash_hmac($algo, $rawPost, $xx))) {
        throw new \Exception('Hook secret does not match.');
    }
    # test commit
    if (isset($_POST) && !empty($_POST)) {
        $payload = json_decode($_POST['payload'], true);
        $module->updateREDCapRepositoryWithLastCommit($payload);
    } else {
        throw new \Exception("something went wrong!");
    }
} catch (\Exception $e) {
    $module->emError($e->getMessage());
    http_response_code(404);
    echo json_encode(array('status' => 'error', 'message' => $e->getMessage()));
}
