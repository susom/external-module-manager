<?php
namespace Stanford\ProjectPortal;
/** @var \Stanford\ExternalModuleManager\ExternalModuleManager $module */

if (isset($_GET['prefix'])) {

  $prefix = $_GET['prefix'];
  
  $result = [
    "schemaVersion" => 1,
    "label" => $prefix,
    "message" => "Test Output",
    "color" => "orange"
  ];
    
} else {
  
  $result = [
    "schemaVersion" => 1,
    "label" => "missing Prefix",
    "message" => "Test Output",
    "color" => "orange"
  ];

}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
