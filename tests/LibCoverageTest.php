<?php
namespace tests\LibCoverage;

use LibCoverage\LibCoverage;

class LibCoverageTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $__SERVER = $_SERVER;
        $old = LibCoverage::_();

        $path = LibCoverage::_()->getClassTestPath(LibCoverage::class);
        LibCoverage::Begin(LibCoverage::class);
        LibCoverage::_()->getClassTestPath(LibCoverage::class);
        LibCoverage::_()->cleanDirectory($path);
        
        ////
        LibCoverageEx::_();
        LibCoverageEx::_(LibCoverageEx::_());
        define('__SINGLETONEX_REPALACER',SingletonExObject::class . '::CreateObject');
        LibCoverage::_($old);

        LibCoverageEx::_();

        //*
        LibCoverageProject::makeData($path);
        
        LibCoverageProject::_()->init([
            'path'=>$path,
            'path_dump'=>'path_dump',
            'path_report'=>'path_report',
            'path_data'=>'path_data',
            //'namespace' => 'A',
        ])->isInited();
        
        $lp =  LibCoverageProject::_();
        $ll = LibCoverage::_();
        
        LibCoverage::_(LibCoverageProject::_());
        LibCoverage::NewProject();
        LibCoverage::NewProject();
        LibCoverage::Cloze();

        // 模板里的方法名来自反射: 只列本类声明的 public 方法, 继承来的不列
        $this->assertTemplate(
            $path.'tests/AppTest.php',
            ['App::_()->foo();', 'App::_()->bar($a, $b, ...$rest);'],
            ['inherited']
        );
        $this->assertTemplate($path.'tests/AppInterfaceTest.php', ['AppInterface::_()->run();'], []);
        $this->assertTemplate($path.'tests/AppTraitTest.php', ['AppTrait::_()->traitFoo();'], []);
        // 源文件载入失败(缺依赖), 不算致命: 模板里不写调用
        $this->assertTemplate($path.'tests/AppMissingDependencyTest.php', [], ['::_()->']);

        LibCoverageProject::_($lp);
        LibCoverage::_($ll);

        //ob_start();
        LibCoverage::_()->doPause();
        LibCoverageProject::Begin(LibCoverageProject::class);
        LibCoverageProject::End();
        LibCoverage::_()->doResume();
        LibCoverageProject::Report();
        LibCoverageMore::_()->init(LibCoverageProject::_()->options);
        LibCoverageMore::_()->testProtectedMethods($this);

        $_SERVER = $__SERVER;
        LibCoverage::_()->cleanDirectory($path);
        LibCoverage::_($old);
        LibCoverage::End();
        
    }
    /**
     * 检查生成的测试模板: 该有的调用要有, 不该有的不能有
     * @param array<string> $has
     * @param array<string> $has_not
     */
    protected function assertTemplate($file, array $has, array $has_not)
    {
        $data = (string)file_get_contents($file);
        foreach ($has as $v) {
            $this->assertStringContainsString($v, $data);
        }
        foreach ($has_not as $v) {
            $this->assertStringNotContainsString($v, $data);
        }
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

abstract class AppBase
{
    public function inherited()
    {
        var_dump(DATE(DATE_ATOM));
    }
}

class App extends AppBase
{
    public function foo()
    {
        var_dump(DATE(DATE_ATOM));
    }
    public function bar(\$a, \$b = 1, ...\$rest)
    {
        var_dump(DATE(DATE_ATOM));
    }
}
EOT;
        @mkdir($path.'src');
        file_put_contents($path.'src/App.php',$str);
$str=<<<EOT
<?php
namespace MyProject;

interface AppInterface
{
    public function run();
}
EOT;
        file_put_contents($path.'src/AppInterface.php',$str);
$str=<<<EOT
<?php
namespace MyProject;

trait AppTrait
{
    public function traitFoo()
    {
        var_dump(DATE(DATE_ATOM));
    }
}
EOT;
        file_put_contents($path.'src/AppTrait.php',$str);
$str=<<<EOT
<?php
namespace MyProject;

class AppMissingDependency extends \MissingVendor\NotInstalled
{
}
EOT;
        file_put_contents($path.'src/AppMissingDependency.php',$str);
        @mkdir($path.'src/sub');
        file_put_contents($path.'src/sub/emptyfile.txt', DATE(DATE_ATOM));

        @mkdir($path.'path_dump');
        file_put_contents($path.'path_dump/emptyfile.txt', DATE(DATE_ATOM));
        @mkdir($path.'tests');
    }

}
class LibCoverageEx extends LibCoverage
{
    public function createReportTest()
    {
        return $this->createReport();
    }}
class LibCoverageMore extends LibCoverage
{
    protected function isSkip()
    {
        return true;
    }
    public function testProtectedMethods($case)
    {
        //getComponenetPathByKey('');
        $this->options['path'] = LibCoverage::_()->getClassTestPath(LibCoverage::class);
        $this->options['path_dump'] = LibCoverage::_()->getClassTestPath(LibCoverage::class).$this->options['path_dump'] ;

        $this->addExtFile(__FILE__);
        $this->pre_begin(LibCoverageMore::class);
        $this->doBegin(LibCoverageMore::class);
        $this->doEnd();
        $this->post_end();

        $this->testCaseObjectToClass($case);

    }

}
