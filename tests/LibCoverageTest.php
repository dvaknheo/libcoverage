<?php
namespace tests;

use LibCoverage\LibCoverage;

class LibCoverageTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $__SERVER = $_SERVER;
        $pwd = getcwd();
        $old = LibCoverage::_();

        $path = LibCoverage::_()->getClassTestPath(LibCoverage::class);
        LibCoverage::Begin(LibCoverage::class);
        LibCoverage::_()->getClassTestPath(LibCoverage::class);
        LibCoverage::_()->cleanDirectory($path);
        LibCoverage::_()->showAllReport();
        
        ////
        LibCoverageEx::_();
        LibCoverageEx::G(LibCoverageEx::_());
        define('__SINGLETONEX_REPALACER',SingletonExObject::class . '::CreateObject');
        LibCoverageEx::_();
        LibCoverage::_($old);

        //*
        LibCoverageProject::makeData($path);
        
        LibCoverageProject::_()->init([
            'path'=>$path,
            'path_dump'=>'path_dump',
            'path_report'=>'path_report',
            'path_data'=>'path_data',
            //'namespace' => 'A',
        ])->isInited();
        
        LibCoverageProject::_()->createProject();
        LibCoverageProject::_()->createProject();

        echo "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n";
        include $path.'src/App.php';
        //ob_start();
        LibCoverageProject::Begin("MyProject\\App");
        try{
            (new \MyProject\App)->foo();
        }catch(\Exception $ex){};
        LibCoverageProject::End();
        LibCoverage::Begin(LibCoverage::class);
        LibCoverageProject::_()->showAllReport();
        
                echo "zzzzzzzzzzzzzzzzzzzzzzzzz\n";

        //LibCoverageEx::_()->doTestMore();
        //LibCoverageEx::_()->addExtFile('t');
        
        ////]]]]
        //LibCoverageEx::G(new LibCoverageEx)->init($old->options)->createReportTest(); //这个想测 include 那段，没成

        //*/
        ///override
        // LibCoverageOverride::_()->init(['override_class'=>LibCoverageEx::class]);
        // LibCoverageOverride::_()->init(['override_class'=>'NoExists']);
        // LibCoverageOverride::_()->init(['override_class'=>LibCoverageOverride::class]);

        // $t=$_SERVER;
        // $_SERVER['argv'][0]='Standard input code';
        // LibCoverageOverride::Begin(LibCoverage::class);
        // LibCoverageOverride::End();
        // LibCoverageOverride::_()->showAllReport();

        $_SERVER = $__SERVER;
        LibCoverage::_()->cleanDirectory($path);
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
class LibCoverageProject extends LibCoverage
{
    public static function makeData($path)
    {
        @mkdir($path, 0777, true);
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
        @mkdir($path.'tests');
    }

}
class LibCoverageEx extends LibCoverage
{
    public function createReportTest()
    {
        return $this->createReport();
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

        $path = LibCoverage::_()->getClassTestPath(LibCoverage::class);
        LibCoverage::_()->init(['path'=>$path]);
        
        LibCoverage::Begin(LibCoverage::class);

        $path = LibCoverage::_()->getClassTestPath(LibCoverage::class);

        LibCoverage::_()->showAllReport();
        //// 次要流程
        //        $path = realpath($path);
        LibCoverage::CreateTestFiles(__DIR__.'/../src',$path);
        
        //LibCoverage::End(); //本例特殊, End 之后就停止跟踪了

*/