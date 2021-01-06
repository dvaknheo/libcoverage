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

////////

$options=[
	//'namespace'=>'LibCoverage',
];

LibCoverage\LibCoverage::G()->init($options);
