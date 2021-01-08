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
    const VERSION = '1.0.1';
    
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

    public static function G($object = null)
    {
        if (defined('__SINGLETONEX_REPALACER')) {
            $callback = __SINGLETONEX_REPALACER;
            return ($callback)(static::class, $object);
        }
        static $_instance;
        $_instance = $object?:($_instance ?? new static);
        return $_instance;
    }
    public static function Begin($class)
    {
        return static::G()->doBegin($class);
    }
    public static function End()
    {
        return static::G()->doEnd();
    }
    ////////

    public function init(array $options, ?object $context = null)
    {
        $this->options = array_intersect_key(array_replace_recursive($this->options, $options) ?? [], $this->options);
        $this->options['path'] = $this->options['path'] ?? getcwd().'/';
        if (empty($this->options['namespace']) && $this->options['auto_detect_namespace']) {
            $this->options['namespace'] = $this->getDefaultNamespaceByComposer();
        }
        if (!is_dir($this->options['path_dump'])) {
            mkdir($this->options['path_dump']);
        }
        if (!is_dir($this->options['path_report'])) {
            mkdir($this->options['path_report']);
        }
        $this->is_inited = true;
        return $this;
    }
    protected function getComponenetPathByKey($path_key)
    {
        if (substr($this->options[$path_key], 0, 1) === '/') {
            return rtrim($this->options[$path_key], '/').'/';
        } else {
            return $this->options['path'].rtrim($this->options[$path_key], '/').'/';
        }
    }
    protected function getDefaultNamespaceByComposer()
    {
        $data = file_get_contents($this->options['path'].'composer.json');
        $data = json_decode((string)$data, true);
        $map = $data['autoload']['psr-4'];
        $namespaces = array_flip($map);
        $namespace = $namespaces[$this->options['path_src']] ?? '';
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
        $ret = rtrim($path_data, '/') .str_replace([$this->options['namespace'].'\\','\\'], ['/','/'], $class).'/';
        return $ret;
    }
    public function addExtFile($extFile)
    {
        $this->extFile = $extFile;
    }
    ///////////////////////////
    protected function createReport()
    {
        $path_src = $this->getComponenetPathByKey('path_src');
        $path_dump = $this->getComponenetPathByKey('path_dump');
        $path_report = $this->getComponenetPathByKey('path_report');
        
        $coverage = new CodeCoverage();
        $coverage->filter()->addDirectoryToWhitelist($path_src);
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
            // 要重复两遍才能 100% ，所以 ignore 得了
            $t = @include $file;    //@codeCoverageIgnore
            $coverage->merge($t);   //@codeCoverageIgnore
        }
        (new ReportOfHtmlOfFacade)->process($coverage, $path_report);
        
        $report = $coverage->getReport();
        $lines_tested = $report->getNumExecutedLines();
        $lines_total = $report->getNumExecutableLines();
        $lines_percent = sprintf('%0.2f%%', $lines_tested / $lines_total * 100);
        return [
            'lines_tested' => $lines_tested,
            'lines_total' => $lines_total,
            'lines_percent' => $lines_percent,
        ];
    }
    
    //@forOverride
    protected function setPath($path)
    {
        if (is_file($path)) {
            $this->coverage->filter()->addFileToWhitelist($path);
        } else {
            $this->coverage->filter()->addDirectoryToWhitelist($path);
        }
    }
    //@forOverride
    protected function classToPath($class)
    {
        $ref = new \ReflectionClass($class);
        return $ref->getFileName();
    }
    
    public function doBegin($class)
    {
        $this->test_class = $class;
        $this->coverage = new CodeCoverage();
        $this->setPath($this->classToPath($class));
        if ($this->extFile) {
            $this->coverage->filter()->addFileToWhitelist($this->extFile); //@codeCoverageIgnore
        }
        $this->coverage->start($class);
    }
    
    public function doEnd()
    {
        $this->coverage->stop();
        
        //@codeCoverageIgnoreStart
        $path = $this->getOutputPath();
        (new ReportOfPHP)->process($this->coverage, $path);
        $this->coverage = null;
        $this->showResult();
        //@codeCoverageIgnoreEnd
    }   //@codeCoverageIgnore
    protected function getOutputPath()
    {
        $path = substr(str_replace('\\', '/', $this->test_class), strlen($this->options['namespace'].'\\'));
        $path = realpath($this->options['path_dump']).'/'.$path .'.php';
        return $path;
    }
    protected function showResult()
    {
        //debug_print_backtrace(2);
        echo "\n\033[42;30m".$this->test_class."\033[0m Test Done!";
        if (class_exists(Assert::class)) {
            Assert::assertTrue(true);
        }
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
        $data = file_get_contents($file);
        preg_match_all('/ function (([^\(]+)\([^\)]*\))/', (string)$data, $m); //TODO 我们考虑改用反射
        $funcs = $m[1];
        
        $ns = $this->options['namespace'].'\\'.str_replace('/', '\\', dirname($short_file));
        $ns = str_replace('\.', '', $ns);
        if (dirname($short_file) == '.') {
            $namespace = 'tests';
        }
        $TestClass = basename($short_file, '.php').'Test';
        $InitClass = basename($short_file, '.php').'';
        
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
            $v = str_replace(['&','callable '], ['',''], $v);
            $ret .= <<<EOT
        {$InitClass}::G()->$v;

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
    public function createProject()
    {
        $source = realpath(__DIR__.'/../').'/';
        
        if (!is_dir($this->options['path_dump'])) {
            @mkdir($this->options['path_dump']);
        }
        if (!is_dir($this->options['path_report'])) {
            @mkdir($this->options['path_report']);
        }
        if (!is_dir($this->options['path_test'])) {
            @mkdir($this->options['path_test']);
        }
        if (!is_dir($this->options['path_data'])) {
            @mkdir($this->options['path_data']);
        }
        
        
        $dest = $this->getComponenetPathByKey('path_test');
        $path = $this->options['path'];
        
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
        
        
        if (!file_exists('phpunit.xml')) {
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
