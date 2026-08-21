<?php
namespace tests\LibCoverage;

use LibCoverage\GroupCoverageRunner;
use LibCoverage\LibCoverage;

class GroupCoverageRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
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
        LibCoverage::_()->doPause();
        GroupCoverageRunner::_()->doBegin($name, $group);
        try{
            include $path."src/App.php";
            (new \GroupCoverageRunnerApp)->foo();
        }catch(\Exception $ex){
            echo $ex->getTraceAsString();
        }
        GroupCoverageRunner::_()->doEnd();
        LibCoverage::Begin(GroupCoverageRunner::class);

        GroupCoverageRunner::_()->showAllReport();

        GroupCoverageRunnerEx::_()->testPostEnd();  
        GroupCoverageRunnerEx::_()->testPreBegin($name, $group);

        //*/

        LibCoverage::_()->cleanDirectory($path);

        $old = LibCoverage::_();
        define('__SINGLETONEX_REPALACER',GroupCoverageSingletonExObject::class . '::CreateObject');
        LibCoverage::_($old);
        GroupCoverageRunner::_();
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
    public function testPostEnd()
    {
        $this->post_end();
    }
    public function testPreBegin(string $name, string $group = ''): void
    {
        $this->coverage = null;
        $this->pre_begin($name, $group);
    }

}