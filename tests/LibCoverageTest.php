<?php
namespace tests;

use LibCoverage\LibCoverage;

class LibCoverageTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        $old = LibCoverage::G();
        LibCoverage::Begin(LibCoverage::class);
        ////[[[[
        $path = LibCoverage::G()->getClassTestPath(LibCoverage::class);
        $path=$path.'tmp';
        @mkdir($path);
        LibCoverage::CreateTestFiles(__DIR__.'/../src',$path);
        LibCoverage::CreateTestFiles(__DIR__.'/../src',$path);
        LibCoverage::G()->cleanDirectory($path);
        LibCoverage::G()->showAllReport();
        ////
        $path = LibCoverage::G()->getClassTestPath(LibCoverage::class);
        define('__SINGLETONEX_REPALACER',SingletonExObject::class . '::CreateObject');
        LibCoverageEx::G(new LibCoverageEx);
        LibCoverageEx::G()->init([
            'path'=>$path,
            'path_dump'=>'path_dump',
            'path_report'=>'path_report',
            'path_data'=>'path_data',
        ])->isInited();
        LibCoverageEx::Begin(LibCoverage::class);
        
        LibCoverageEx::G()->doTestMore();
        
        LibCoverageEx::G()->addExtFile('t');
        

        LibCoverageEx::G()->doTestMore2();
        
        
        ////]]]]
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
        $_instance[$class] = $object?:($_instance[$class]??($_instance[$class]??new static));
        return $_instance[$class];
    }

}
class LibCoverageEx extends LibCoverage
{
    public function doTestMore()
    {
        $this->options['mypath']='/test/';
        $this->getComponenetPathByKey('mypath');
        $this->getOutputPath();
        $this->showResult();
        
        $this->setPath($this->options['path']);
        $this->setPath($this->classToPath(LibCoverage::class));
    }
    public function doTestMore2()
    {
        $this->makeDir('tmp3/4',$this->options['path']);
        $this->cleanDirectory($this->options['path']);
    }
    //
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