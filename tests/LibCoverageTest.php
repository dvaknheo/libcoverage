<?php
namespace tests;

use LibCoverage\LibCoverage;

class LibCoverageTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $old = LibCoverage::G();
        $pwd = getcwd(); // chdir 没搞懂
        LibCoverage::Begin(LibCoverage::class);
        ////[[[[
        $path = LibCoverage::G()->getClassTestPath(LibCoverage::class);
        
        LibCoverage::G()->cleanDirectory($path);
        LibCoverage::G()->showAllReport();
        ////
        $path = LibCoverage::G()->getClassTestPath(LibCoverage::class);
        define('__SINGLETONEX_REPALACER',SingletonExObject::class . '::CreateObject');
        LibCoverageEx::makeData($path);
        LibCoverageEx::G(new LibCoverageEx);
        chdir($path);
        LibCoverageEx::G()->init([
            'path'=>$path,
            'path_dump'=>'path_dump',
            'path_report'=>'path_report',
            'path_data'=>'path_data',
        ])->isInited();
        
        LibCoverageEx::Begin(LibCoverage::class);
        
        LibCoverageEx::G()->doTestMore();
        LibCoverageEx::G()->addExtFile('t');
        
        ////]]]]
        chdir($pwd); // chdir 没搞懂
        LibCoverageEx::G(new LibCoverageEx)->init($old->options)->createReportTest(); //这个想测 include 那段，没成
        
        ///override
        LibCoverageOverride::G()->init(['override_class'=>LibCoverageEx::class]);
        LibCoverageOverride::G()->init(['override_class'=>'NoExists']);
        LibCoverageOverride::G()->init(['override_class'=>LibCoverageOverride::class]);

        LibCoverage::G($old);
        LibCoverage::End();
        
    }
}
class SingletonExObject
{
    public static function CreateObject($class, $object)
    {
        static $_instance;
        $_instance = $_instance??[];
        $_instance[$class] = $object?:($_instance[$class]??($_instance[$class]??new $class));
        return $_instance[$class];
    }

}
class LibCoverageEx extends LibCoverage
{
    public function createReportTest()
    {
        return $this->createReport();
    }
    public static function makeData($path)
    {
$str=<<<EOT
{
    "autoload": {
        "psr-4": {
            "MyProject\\\\": "src"
        }
    }
}
EOT;
        file_put_contents($path.'composer.json',$str);
$str=<<<EOT
<?php
namespace MyProject;

class App
{
    public function foo()
    {
        var_dump(DATE(DATE_ATOM));
    }
}
EOT;
        @mkdir($path.'src');
        file_put_contents($path.'src/App.php',$str);
    }
    public function doTestMore()
    {

        $this->options['mypath']='/test/';
        $this->getComponenetPathByKey('mypath');
        $this->getOutputPath();
        $this->showResult();
        
        // 这两个可以优化？
        $this->setPath($this->options['path']);
        $this->setPath($this->classToPath(LibCoverage::class));
        ////////////////
        
        $this->makeDir('a/b/c',$this->options['path_src']);
        rmdir($this->options['path_dump']);
        rmdir($this->options['path_report']);
        $this->createProject();
        $this->createProject();
        
        //exit;
        $this->cleanDirectory($this->options['path']);
        
    }
    //
}
class LibCoverageOverride extends LibCoverageEx
{

}
/*

        $path = LibCoverage::G()->getClassTestPath(LibCoverage::class);
        LibCoverage::G()->init(['path'=>$path]);
        
        LibCoverage::Begin(LibCoverage::class);

        $path = LibCoverage::G()->getClassTestPath(LibCoverage::class);

        LibCoverage::G()->showAllReport();
        //// 次要流程
        //        $path = realpath($path);
        LibCoverage::CreateTestFiles(__DIR__.'/../src',$path);
        
        //LibCoverage::End(); //本例特殊, End 之后就停止跟踪了

*/