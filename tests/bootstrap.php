<?php
foreach ([__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../src/LibCoverage.php'] as $file) {
    if (file_exists($file)) {
        require $file;
        break;
    }
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
