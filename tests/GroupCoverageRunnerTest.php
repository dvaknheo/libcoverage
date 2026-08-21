<?php
namespace tests\LibCoverage;

use LibCoverage\GroupCoverageRunner;

use LibCoverage\LibCoverage;

class GroupCoverageRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        LibCoverage::Begin(GroupCoverageRunner::class);
        
        GroupCoverageRunner::_()->init([
            'path_src' => 'src/',
            'path_dump' => 'test_coveragedumps',
            'path_report' => 'test_reports',
            'group' => '',
            'groups' => [],
            'name' => '',
        ]);
        GroupCoverageRunner::_()->getCoverage();
        GroupCoverageRunner::_()->doBegin($name, $group);
        GroupCoverageRunner::_()->doBegin($name, $group);
        GroupCoverageRunner::_()->doEnd();
        //GroupCoverageRunner::_()->createReport(string $path_src, array $groups, string $path_dump, string $path_report);
        GroupCoverageRunner::_()->showAllReport();
        //*/
        
        LibCoverage::End();
    }
}
