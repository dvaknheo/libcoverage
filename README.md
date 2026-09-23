# LibCoverage

[English](README.md) | [中文](README.zh_CN.md)

*** v1.0.8 ***

Author QQ: 85811616

Official QQ Group: 714610448

LibCoverage helps PHP library developers achieve full code-coverage testing, making your PHP code more robust.

## Usage

```
composer require --dev dvaknheo/libcoverage ## install
composer exec libcoverage          # show help
composer exec libcoverage setup    # set up the project
phpunit                            # run PHPUnit unit tests
cat test_reports/index.html        # view the report; you can also open it in a browser
# composer exec libcoverage cloze  # new classes added; fill in test templates
# phpunit tests/AppTest.php && phpunit tests/support.php # regenerate when only a single class has changed
# composer exec libcoverage report  # alternative report generation; already generated during phpunit
```

`composer exec libcoverage setup` creates `phpunit.xml` (if it does not exist) and the supporting files `tests/boostrap.php` and `tests/support.php`.

Run `phpunit`, then open `index.html` in the `test_reports` directory.

The `setup` command also generates corresponding `tests/*Test.php` test templates based on the class files in the `src` directory.

For example, for `src/App.php`:

```php
<?php
namespace MyProject;

class App
{
    public function foo()
    {
        var_dump(DATE(DATE_ATOM));
    }
}
```

`tests/AppTest.php` will be generated:

```php
<?php 
namespace tests\MyProject;

use MyProject\App;

use LibCoverage\LibCoverage;

class AppTest extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        LibCoverage::Begin(App::class);
        
        /* //
        App::_()->foo();
        //*/
        
        LibCoverage::End();
    }
}

```

The test code in the middle is up to you — write tests that achieve 100% coverage for the current class.

`composer exec libcoverage cloze` is used to fill in test files that were added later. Existing test files will not be overwritten.

`phpunit tests/AppTest.php && phpunit tests/support.php` is a trick for when only a single file has been modified.

Below is a screenshot of LibCoverage running PHPUnit with full coverage. Example of `test_reports/index.html`:

![capture](docs/capture.png)

## LibCoverage Class Options

Default options:

```php
    public $options = [
        'namespace' => null,
        'path' => null,
        'path_src' => 'src',
        'path_dump' => 'test_coveragedumps',
        'path_report' => 'test_reports',
        'path_test' => 'tests',
        'path_data' => 'tests/data_for_tests',
        'auto_detect_namespace' => true, 
    ];
```

When running `composer exec libcoverage`, these options can be passed in, for example:

`vendor/bin/libcoverage --path='abc' --path-test=test`

You can also adjust these options in `tests/boostrap.php`.

`namespace` is the base namespace of the classes to be tested. If left empty, `auto_detect_namespace` will detect and fill it.

`path` is the base path.

`path_data` is used to store test data.

## LibCoverage Class Public Method Reference

Static methods — common needs are met by calling static methods:

```php
    LibCoverage::_($object=null); // mutable singleton; pass $object to replace the singleton
    LibCoverage::Begin($class);    // start full-coverage tracing for a class
    LibCoverage::End();  // end tracing for a class
    LibCoverage::Report();  // used by support.php to display all reports
    LibCoverage::NewProject();  // create project files; used by the setup command
    LibCoverage::Cloze();  // create test files; used by the cloze command

```

Additional methods:

```php
    LibCoverage::_()->init(array $options, ?object $context = null); // used by boostrap.php for initialization
```

Other less commonly used public methods:

```php
    LibCoverage::_()->doPause() // pause
    LibCoverage::_()->doResume() // resume
    LibCoverage::_()->addExtFile($extFile); // add extra test files, such as global function files
    LibCoverage::_()->getClassTestPath($class); // get the dedicated directory for the test class; default is tests/data_for_test/[ClassName]
    LibCoverage::_()->cleanDirectory($dir);  // helper method for deleting test directories, etc.

```

## Full Coverage Passing Does Not Equal All Functional Tests Passing

The code may use `@codeCoverageIgnore`, `@codeCoverageIgnoreStart`, and `@codeCoverageIgnoreEnd` to skip sections.

Also, conditional short-circuiting may skip subsequent branches.

Also, someone forced to achieve full coverage may not necessarily exercise all logic paths.
