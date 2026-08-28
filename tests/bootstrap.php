<?php
require_once __DIR__ . '/../vendor/autoload.php';

////////
$options=[
    'path' => realpath(__DIR__ .'/../').'/',
    //'namespace' => null,
    //'auto_detect_namespace' => true,
    
    'path_src' => 'src',
    'path_dump' => 'test_coveragedumps',
    'path_report' => 'test_reports',
    'path_data' => 'tests/data_for_tests',
];

LibCoverage\LibCoverage::_()->init($options);
