# LibCoverage

LibCoverage 用于帮助解决 PHP 库的开发者，全覆盖测试。

## 使用方法

```
composer require --dev dvaknheo/libcoverage
# phpunit
# cat test_report/index.html

```

会在 tests 目录下根据 src 目录生成测试文件

运行 phpunit ，然后查看 test_report 下的 index.html

会生成 boostrap.php 和 support.php 文件。

以及根据相应的 src 的类文件生成对应的 *Test.php 测试模板文件


```
<?php
namespace tests;

use MyProject;

class MyProjectTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        \\LibCoverage\\LibCoverage::Begin(MyProject::class);
        
        // you code here
        
        \\LibCoverage\\LibCoverage::End();
    }
}

```
libcoverage 不负责生成 phpunit.xml

### LibCoverage 类选项说明

public $options=[
    'namespace' => null,
    'path'=>'',           // 基准目录
    'path_src'=>'src',      // 代码目录
    'path_dump'=>'test_coveragedumps', // 代码 dump 目录
    'path_report'=>'test_reports',      // 报告目录
    'path_data'=>'tests/data_for_tests',    // 测试数据目录，用于 GetClassTestPath
];

## LibCoverage 类公开方法参考

静态方法，常用需求都是调用静态方法.

LibCoverage::G($object=null); // 单例函数
LibCoverage::Begin($class);    // 开始一个类的全覆盖跟踪
LibCoverage::End();
LibCoverage::CreateTestFiles($source, $dest);  // 创建测试文件,


// 额外的方法
LibCoverage::G()->init(array $options, ?object $context = null); // boostrap.php 用来初始化的.
LibCoverage::G()->showAllReport();  // support.php 用来显示所有报告的

LibCoverage::G()->isInited();
LibCoverage::G()->addExtFile($extFile);
LibCoverage::G()->getClassTestPath($class);
LibCoverage::G()->cleanDirectory($dir);

## 全覆盖测试通过不等于所有功能测试通过