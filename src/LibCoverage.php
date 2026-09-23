<?php declare(strict_types=1);
/**
 * LibCoverage
 * From this time on, you never be alone~
 */
namespace LibCoverage;

use PHPUnit\Framework\Assert;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as ReportOfHtmlOfFacade;
use SebastianBergmann\CodeCoverage\Report\PHP as ReportOfPHP;

class LibCoverage
{
    const VERSION = '1.0.8';
    
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
    public $is_inited = true;
    
    protected $extFile = null;
    protected $coverage;
    protected $test_class;
    protected $test_object;
    protected $filter;
    
    protected $is_skip = false;

    protected static $_instances = [];
    
    //embed
    /**
     * @return static
     */
    public static function _($object = null)
    {
        if (defined('__SINGLETONEX_REPALACER')) {
            $callback = __SINGLETONEX_REPALACER;
            return ($callback)(static::class, $object);
        }
        if ($object) {
            self::$_instances[static::class] = $object;
            return $object;
        }
        $me = self::$_instances[static::class] ?? null;
        if (null === $me) {
            $me = new static();
            self::$_instances[static::class] = $me;
        }
        
        return $me;
    }
    /**
    * @return static
    */
    public static function G($object = null)
    {
        return static::_($object);
    }
    public function __construct()
    {
    }
    public static function Begin($class)
    {
        return static::G()->doBegin($class);
    }
    public static function End()
    {
        return static::G()->doEnd();
    }
    public static function Report()
    {
        return static::_()->showAllReport();
    }
    public static function Cloze()
    {
        return static::_()->createTestFiles();
    }

    public static function NewProject()
    {
        return static::_()->createProject();
    }
    /**
     * 建立测试类专用目录 path_data/类名/, 返回目录路径
     */
    public static function CreateTestDir($class = null)
    {
        return static::_()->doCreateTestDir($class);
    }
    /**
     * 清理测试类专用目录 path_data/类名/, 返回目录路径
     */
    public static function CleanTestDir($class = null)
    {
        return static::_()->doCleanTestDir($class);
    }

    ////////
    /**
     * Summary of init
     * @param array<string,mixed> $options
     * @param ?object $context
     * @return static
     */
    public function init(array $options, ?object $context = null)
    {
        $this->options = array_intersect_key(array_replace_recursive($this->options, $options), $this->options);
        $this->options['path'] = $this->options['path'] ?? getcwd().'/';
        if (empty($this->options['namespace']) && $this->options['auto_detect_namespace']) {
            $this->options['namespace'] = $this->getDefaultNamespaceByComposer();
        }
        $this->is_skip = $this->isSkip();
        $this->make_sub_dir('path_dump');
        $this->make_sub_dir('path_report');

        $this->create_coverage();

        $this->is_inited = true;
        return $this;
    }
    protected function create_coverage()
    {
        $this->filter = new \SebastianBergmann\CodeCoverage\Filter();
        $this->coverage = new CodeCoverage(
            (new \SebastianBergmann\CodeCoverage\Driver\Selector())->forLineCoverage($this->filter),
            $this->filter
        );
    }
    protected function isSkip()
    {
        $c_args = [
            '--coverage-clover',
            '--coverage-crap4j',
            '--coverage-html',
            '--coverage-php',
            '--coverage-text',
        ];
        $flag = array_reduce(
            $c_args,
            function ($flag, $v) {
                return $flag || in_array($v, $_SERVER['argv'] ?? []);
            },
            false
        );
        return $flag;
    }
    protected static function IsAbsPath($path)
    {
        $is_abs = preg_match('#^(?:/|[a-zA-Z]:[\\\\/]|\\\\{2})#', $path ?? '') > 0;
        return $is_abs;
    }
    protected function getComponenetPathByKey($path_key)
    {
        $full_file = $this->options[$path_key];
        $is_abs = static::IsAbsPath($full_file);
        if ($is_abs) {
            return rtrim($this->options[$path_key], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        } else {
            return $this->options['path'].rtrim($this->options[$path_key], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        }
    }
    protected function getDefaultNamespaceByComposer()
    {
        $data = file_get_contents($this->options['path'].'composer.json');
        $data = json_decode((string)$data, true);
        $map = $data['autoload']['psr-4'];
        $namespaces = array_flip($map);
        $namespace = $namespaces[$this->options['path_src']] ?? ($namespaces[$this->options['path_src'].'/'] ?? '');
        $namespace = rtrim($namespace, '\\');
        return $namespace;
    }
    public function isInited():bool
    {
        return $this->is_inited;
    }
    public function getClassTestPath($class)
    {
        $path_data = $this->getComponenetPathByKey('path_data');
        $ret = rtrim($path_data, DIRECTORY_SEPARATOR) .str_replace([$this->options['namespace'].'\\','\\'], ['/','/'], $class).DIRECTORY_SEPARATOR;
        $ret = str_replace(['\\','/'], [DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR], $ret);
        return $ret;
    }
    public function addExtFile($extFile)
    {
        $this->extFile = $extFile;
        $this->addPathToFilter($this->filter, $extFile);
    }
    ///////////////////////////
    protected function addPathToFilter($target, $path): void
    {
        if (!file_exists($path)) {
            return; //@codeCoverageIgnore
        }
        if (is_file($path)) {
            $target->includeFiles([$path]);
            return;
        }
        $dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($dir);
        foreach ($it as $f) {
            if (substr($f, -4) === '.php') {
                $target->includeFiles([$f]);
            }
        }
    }
    protected function createReport()
    {
        $path_src = $this->getComponenetPathByKey('path_src');
        $path_dump = $this->getComponenetPathByKey('path_dump');
        $path_report = $this->getComponenetPathByKey('path_report');
        
        $filter = new \SebastianBergmann\CodeCoverage\Filter();
        $this->addPathToFilter($filter, $path_src);
        $coverage = new CodeCoverage(
            (new \SebastianBergmann\CodeCoverage\Driver\Selector())->forLineCoverage($filter),
            $filter
        );
        $coverage->setTests([
          'T' => [
            'size' => 'unknown',
            'status' => -1,
          ],
        ]);
        $directory = new \RecursiveDirectoryIterator($path_dump, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);

        $iterator = new \RecursiveIteratorIterator($directory);
        $files = \iterator_to_array($iterator, false);
        foreach ($files as $file) {
            if (substr($file, -4) !== '.php') {
                continue;
            }
            $t = static::include_file($file);
            //copy($file,$file.'.bak-'.DATE('Y-m-d_H-i-s'));
            $coverage->merge($t);
        }
        (new ReportOfHtmlOfFacade)->process($coverage, $path_report);
        
        $report = $coverage->getReport();
        $lines_tested = $report->numberOfExecutedLines();
        $lines_total = $report->numberOfExecutableLines();
        $lines_percent = sprintf('%0.2f%%', $lines_tested / $lines_total * 100);
        return [
            'lines_tested' => $lines_tested,
            'lines_total' => $lines_total,
            'lines_percent' => $lines_percent,
        ];
    }
    protected static function include_file($file)
    {
        return include $file;
    }

    //@forOverride
    protected function classToPath($class)
    {
        $ref = new \ReflectionClass($class);
        return $ref->getFileName();
    }
    public function doPause()
    {
        if ($this->test_class && $this->coverage) {
            $this->coverage->stop();
        }
    }
    public function doResume()
    {
        if ($this->test_class) {                //@codeCoverageIgnore
            $this->doBegin($this->test_class);  //@codeCoverageIgnore
        }
    }
    protected function testCaseObjectToClass($class_or_object)
    {
        return substr((string)get_class($class_or_object), strlen("tests\\"), 0 - strlen("Test"));
    }
    public function doBegin($class_or_object)
    {
        if (\is_object($class_or_object)) {
            $this->test_object = $class_or_object; //@codeCoverageIgnore
            $class = $this->testCaseObjectToClass($class_or_object);  //@codeCoverageIgnore
        } else {
            $class = $class_or_object;
        }
        $this->test_class = $class;

        if ($this->isSkip()) {
            return;
        }
        $this->pre_begin($class); //@codeCoverageIgnore
        $this->coverage->start($class);//@codeCoverageIgnore
    }
    protected function pre_begin($class)
    {
        $this->addPathToFilter($this->filter, $this->classToPath($class));
        if ($this->extFile) {
            $this->addPathToFilter($this->filter, $this->extFile);
        }
        echo "\n\033[42;30m".$class."\033[0m Test Start\n";
    }
    public function doEnd()
    {
        if (class_exists(Assert::class)) {
            Assert::assertTrue(true);
        }
        if ($this->isSkip()) {
            return;
        }
        $this->coverage->stop();
        $this->post_end();//@codeCoverageIgnore
    }
    protected function post_end()
    {
        $path = $this->getOutputPath();
        (new ReportOfPHP)->process($this->coverage, $path);
        $this->showResult();
    }
    protected function getOutputPath()
    {
        $path = substr(str_replace('\\', '/', $this->test_class), strlen($this->options['namespace'].'\\'));
        $path = $this->getComponenetPathByKey('path_dump').$path .'.php';
        return $path;
    }
    protected function showResult()
    {
        echo "\n\033[42;30m".$this->test_class."\033[0m Test Done!";
        echo "\n";
    }
    public function showAllReport()
    {
        $data = $this->createReport();
        echo "\nSTART CREATE REPORT AT " .DATE(DATE_ATOM)."\n";
        echo "Output File:\n\n\033[42;30mfile://".$this->getComponenetPathByKey('path_report')."index.html" ."\033[0m\n";
        echo "\n\033[42;30m All Done \033[0m Test Done!";
        echo "\nTest Lines: \033[42;30m{$data['lines_tested']}/{$data['lines_total']}({$data['lines_percent']})\033[0m\n";
        echo "\n\n";
        if (class_exists(Assert::class)) {
            Assert::assertTrue(true);
        }
    }
    ////
    public function cleanDirectory($dir)
    {
        $dir = rtrim($dir, '/');
        if (!is_dir($dir)) {
            return true;   //@codeCoverageIgnore
        }
        $handle = opendir($dir);
        if ($handle === false) {
            return false;   //@codeCoverageIgnore
        }
        $result = true;
        while ($file = readdir($handle)) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            if (is_dir("$dir/$file")) {
                $result = $this->cleanDirectory("$dir/$file") && $result;
            } elseif ($file === '.gitignore') {
                $result = false;
                continue;
            } else {
                $result = unlink("$dir/$file") && $result;
            }
        }
        closedir($handle);
        if ($result) {
            $result = @rmdir($dir);
        }
        return $result;
    }
    /**
     * 建立测试类专用目录 path_data/类名/, 返回目录路径
     * @param ?string $class 不填就用 Begin() 的类
     * @return string
     */
    public function doCreateTestDir($class = null)
    {
        $path = $this->getTestDirPath($class);
        if ($path !== '' && !is_dir($path)) {
            mkdir($path, 0777, true);
        }
        return $path;
    }
    /**
     * 清理测试类专用目录 path_data/类名/, 返回目录路径
     * @param ?string $class 不填就用 Begin() 的类
     * @return string
     */
    public function doCleanTestDir($class = null)
    {
        $path = $this->getTestDirPath($class);
        $this->cleanDirectory($path);
        return $path;
    }
    /**
     * 测试类专用目录路径: 不填类名就用 Begin() 的类, 都没有就没有目录
     * @param ?string $class
     * @return string
     */
    protected function getTestDirPath($class = null)
    {
        $class = $class ?? $this->test_class;
        if (empty($class)) {
            return '';      // 还没 Begin() 过, 不知道是谁的目录
        }
        return $this->getClassTestPath($class);
    }
    ///////////////////////


    public function createTestFiles()
    {
        $source = $this->getComponenetPathByKey('path_src');
        $dest = $this->getComponenetPathByKey('path_test');
        
        $directory = new \RecursiveDirectoryIterator($source, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = \iterator_to_array($iterator, false);
        foreach ($files as $file) {
            $short_file = substr($file, strlen($source));
            
            $this->makeDir($short_file, $dest);
            $data = $this->makeTest($file, $short_file);
            
            $file_name = $dest.str_replace('.php', 'Test.php', $short_file);
            if (is_file($file_name)) {
                echo "Skip Existed File:".$file_name."\n";
                continue;
            }
            file_put_contents($file_name, $data);
            echo "Create File:".$file_name."\n";
        }
    }
    protected function makeDir($short_file, $dest)
    {
        $blocks = explode(DIRECTORY_SEPARATOR, $short_file);
        array_pop($blocks);
        $full_dir = $dest;
        foreach ($blocks as $t) {
            $full_dir .= DIRECTORY_SEPARATOR.$t;
            if (!is_dir($full_dir)) {
                mkdir($full_dir);
            }
        }
    }
    protected function makeTest($file, $short_file)
    {
        $ns = $this->options['namespace'].'\\'.str_replace('/', '\\', dirname($short_file));
        $ns = str_replace('\.', '', $ns);
        $TestClass = basename($short_file, '.php').'Test';
        $InitClass = basename($short_file, '.php');
        $funcs = $this->getMethodCallsByReflection($file, $ns.'\\'.$InitClass);
        
        $ret = "<"."?php \n";
        $ret .= <<<EOT
namespace tests\\{$ns};

use {$ns}\\{$InitClass};

use LibCoverage\LibCoverage;

class $TestClass extends \PHPUnit\Framework\TestCase
{
    public function testAll()
    {
        LibCoverage::Begin({$InitClass}::class);
        
        /* //

EOT;
        foreach ($funcs as $v) {
            $ret .= <<<EOT
        {$InitClass}::_()->$v;

EOT;
        }
        $ret .= <<<EOT
        //*/
        
        LibCoverage::End();
    }
}

EOT;
        return $ret;
    }
    /**
     * 用反射取出模板里要写的方法调用: 只取本类声明的 public 方法, 继承来的不算。
     * @return array<string>
     */
    protected function getMethodCallsByReflection($file, $class)
    {
        $ref = $this->reflectClassOfFile($file, $class);
        if (null === $ref) {
            return [];
        }
        $ret = [];
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;   // 父类带来的方法, 归父类的测试模板
            }
            $ret[] = $this->makeMethodCall($method);
        }
        return $ret;
    }
    /**
     * 反射出文件里的类(接口/性状也算)。类没被 autoload 到, 就载入源文件再反射。
     * @return ?\ReflectionClass<object>
     */
    protected function reflectClassOfFile($file, $class)
    {
        if (!$this->isReflectable($class) && substr($file, -4) === '.php') {
            try {
                include_once $file;
            } catch (\Throwable $ex) {
                return null;    //@codeCoverageIgnore 源文件载入失败(缺依赖/语法错), 当没有方法处理
            }
        }
        if (!$this->isReflectable($class)) {
            return null;        // 文件里没有这个类, 比如 src 目录里的非类文件
        }
        return new \ReflectionClass($class);
    }
    protected function isReflectable($class)
    {
        return class_exists($class) || interface_exists($class) || trait_exists($class);
    }
    /**
     * 生成模板里的调用语句: foo() / bar($a, $b) / baz(...$args)
     */
    protected function makeMethodCall(\ReflectionMethod $method)
    {
        $args = [];
        foreach ($method->getParameters() as $parameter) {
            $args[] = ($parameter->isVariadic() ? '...' : '').'$'.$parameter->getName();
        }
        return $method->getName().'('.implode(', ', $args).')';
    }
    protected function make_sub_dir($path_key)
    {
        $path = $this->getComponenetPathByKey($path_key);
        if (!is_dir($path)) {
            mkdir($path);
        }
    }
    public function createProject()
    {
        $source = realpath(__DIR__.'/../').'/';
        $this->make_sub_dir('path_dump');
        $this->make_sub_dir('path_report');
        $this->make_sub_dir('path_test');
        $this->make_sub_dir('path_data');

        $dest = $this->getComponenetPathByKey('path_test');
        $path = realpath($this->options['path']).DIRECTORY_SEPARATOR;
        
        if (!file_exists($dest.'bootstrap.php')) {
            echo "Copy test boostrap file:  '{$dest}support.php' \n";
            copy($source.'tests/bootstrap.php', $dest.'bootstrap.php');
        } else {
            echo "Skip exists test boostrap file:  '{$dest}bootstrap.php' \n";
        }
        if (!file_exists($dest.'support.php')) {
            echo "Copy test support file:  '{$dest}support.php' \n";
            copy($source.'tests/support.php', $dest.'support.php');
        } else {
            echo "Skip exists test support file:  '{$dest}support.php' \n";
        }
        
        
        if (!file_exists($path.'phpunit.xml')) {
            echo "Copy {$path}phpunit.xml \n";
            $data = file_get_contents($source.'phpunit.xml');
            $data = str_replace('LibCoverage', (string)$this->options['namespace'], (string)$data);
            file_put_contents($path.'phpunit.xml', $data);
        } else {
            echo "skip {$path}phpunit.xml \n";
        }
        $this->createTestFiles();
    }
    
    /* //这段代码先记在这里
    public static function SimpleCover($src,$dest)
    {
        $coverage = new \SebastianBergmann\CodeCoverage\CodeCoverage();
        $coverage->filter()->addDirectoryToWhitelist($src);
        $coverage->start(DATE(DATE_ATOM));
        register_shutdown_function(function()use($coverage, $dest){
            $coverage->stop();
            $writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade;
            $writer->process($coverage,$dest);
        });
    }
    //*/
}
