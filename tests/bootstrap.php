<?php
require __DIR__ . '/../src/LibCoverage.php';
//require __DIR__ . '/../vendor/autoload.php';

$c_args=[
    '--coverage-clover',
    '--coverage-crap4j',
    '--coverage-html',
    '--coverage-php',
    '--coverage-text',
];
$flag = array_reduce(
    $c_args,
    function($flag,$v){
        return $flag || in_array($v,$_SERVER['argv']);
    },
    false
);
if($flag) {
    return;
}

////////
$options=[
    //'path' => null,
    //'namespace' => null,
    //'auto_detect_namespace' => true,
    
    //'path_src' => 'src',
    //'path_dump' => 'test_coveragedumps',
    //'path_report' => 'test_reports',
    //'path_data' => 'tests/data_for_tests',
];

LibCoverage\LibCoverage::G()->init($options);
