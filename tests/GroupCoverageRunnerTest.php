<?php
namespace tests\LibCoverage;

use LibCoverage\GroupCoverageRunner;

use LibCoverage\LibCoverage;

class GroupCoverageRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        LibCoverage::Begin(GroupCoverageRunner::class);
        
        /* //
        GroupCoverageRunner::G()->_($object = null);
        GroupCoverageRunner::G()->__construct();
        GroupCoverageRunner::G()->init(array $options, ?object $context = null);
        GroupCoverageRunner::G()->getCoverage();
        GroupCoverageRunner::G()->doBegin(string $name, string $group = '');
        GroupCoverageRunner::G()->doEnd();
        GroupCoverageRunner::G()->createReport(string $path_src, array $groups, string $path_dump, string $path_report);
        GroupCoverageRunner::G()->showAllReport();
        GroupCoverageRunner::G()->createCoverage();
        GroupCoverageRunner::G()->includePath(CodeCoverage $coverage, string $path);
        GroupCoverageRunner::G()->mergeFromDir(CodeCoverage $coverage, string $dir);
        GroupCoverageRunner::G()->fillPartialCoveredFiles(CodeCoverage $coverage);
        GroupCoverageRunner::G()->renderReport(CodeCoverage $coverage, string $path_report);
        //*/
        
        LibCoverage::End();
    }
}
