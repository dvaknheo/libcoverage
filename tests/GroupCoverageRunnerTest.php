<?php
namespace tests\LibCoverage;

use LibCoverage\GroupCoverageRunner;
use LibCoverage\LibCoverage;

class GroupCoverageRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $old = LibCoverage::_();
        $path = LibCoverage::_()->getClassTestPath(GroupCoverageRunner::class);
        
        LibCoverage::Begin(GroupCoverageRunner::class);
        @mkdir($path);
        $this->makeData($path);

        GroupCoverageRunner::_(GroupCoverageRunnerEx::_());
        GroupCoverageRunner::_()->init([
            'path' => $path,
            'path_src' => $path.'src/',
            'path_dump' => 'path_dump',
            'path_report' => $path.'path_report',
            'groups' => [],
        ]);
        $name = 'test1;a,b,c';
        $group = 'group1';
        GroupCoverageRunner::_()->getCoverage();
        GroupCoverageRunner::_()->doBegin($name, $group, $path.'src/', $path.'path_dump/',);
        try{
            include $path."src/App.php";
            (new \GroupCoverageRunnerApp)->foo();
        }catch(\Exception $ex){
            echo $ex->getTraceAsString();
        }
        GroupCoverageRunner::_()->doEnd();
        GroupCoverageRunner::_()->doEnd();

        //createReport(array $groups, string $path_src, string $path_dump, string $path_report);
        GroupCoverageRunnerEx::_()->testCreateReport();

       

        
        define('__SINGLETONEX_REPALACER',GroupCoverageSingletonExObject::class . '::CreateObject');
        LibCoverage::_($old);
        GroupCoverageRunner::_();
        LibCoverage::_()->cleanDirectory($path);
        LibCoverage::End();
    }

    protected function makeData($path)
    {
$str=<<<EOT
<?php
class GroupCoverageRunnerApp
{
    public function foo()
    {
        var_dump(DATE(DATE_ATOM));
    }
}
EOT;
        @mkdir($path.'src');
        file_put_contents($path.'src/App.php',$str);
        //@mkdir($path.'src/sub');
        file_put_contents($path.'src/emptyfile.txt', DATE(DATE_ATOM));
        
        file_put_contents($path.'src/no_tested.php', DATE(DATE_ATOM));
        @mkdir($path.'path_dump/');
    }

}
class GroupCoverageSingletonExObject
{
    public static function CreateObject($class, $object)
    {
        static $_instance;
        $_instance = $_instance??[];
        $_instance[$class] = $object?:($_instance[$class]??($_instance[$class]??new $class));
        return $_instance[$class];
    }

}
class GroupCoverageRunnerEx extends GroupCoverageRunner
{
    public function testCreateReport()
    {
        //createReport(array $groups, string $path_src, string $path_dump, string $path_report);
        $groups = ['group1'];
        $path = LibCoverage::_()->getClassTestPath(GroupCoverageRunner::class);
        $path_src = $path.'src/';
        $path_dump = $path.'path_dump/';
        $path_report = $path.'path_report/';

        GroupCoverageRunnerEx::_()->createReport($groups, $path_src,  $path_dump, $path_report);
    }
}