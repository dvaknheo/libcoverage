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
    public $options = [
        'namespace' => null,
        'auto_detect_namespace' => true,
        'path' => null,
        'path_src' => 'src',
        'path_dump' => 'test_coveragedumps',
        'path_report' => 'test_reports',
        'path_data' => 'tests/data_for_tests',
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
    /////
    public static function CreateTestFiles($source, $dest)
    {
        static::G()->doCreateTestFiles($source, $dest);
    }
    
    ////////

    public function init(array $options, ?object $context = null)
    {
        $this->options = array_intersect_key(array_replace_recursive($this->options, $options) ?? [], $this->options);
        
        $this->options['path'] = $this->options['path'] ?? realpath(__DIR__ .'/..').'/';
        $this->options['path_src'] = $this->getComponenetPathByKey('path_src');
        $this->options['path_dump'] = $this->getComponenetPathByKey('path_dump');
        $this->options['path_report'] = $this->getComponenetPathByKey('path_report');
        $this->options['path_data'] = $this->getComponenetPathByKey('path_data');
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
    public function isInited():bool
    {
        return $this->is_inited;
    }
    public function getClassTestPath($class)
    {
        $ret = rtrim($this->options['path_data'], '/').str_replace([$this->options['namespace'].'\\','\\'], ['/','/'], $class).'/';
        return $ret;
    }
    public function addExtFile($extFile)
    {
        $this->extFile = $extFile;
    }
    ///////////////////////////
    protected function createReport()
    {
        $coverage = new CodeCoverage();
        $coverage->filter()->addDirectoryToWhitelist($this->options['path_src']);
        $coverage->setTests([
          'T' => [
            'size' => 'unknown',
            'status' => -1,
          ],
        ]);
        $directory = new \RecursiveDirectoryIterator($this->options['path_dump'], \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);

        $iterator = new \RecursiveIteratorIterator($directory);
        $files = \iterator_to_array($iterator, false);
        foreach ($files as $file) {
            $t = @include $file;
            $coverage->merge($t);
        }
        (new ReportOfHtmlOfFacade)->process($coverage, $this->options['path_report']);
        
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
        if ($this->options['auto_detect_namespace'] && empty($this->options['namespace'])) {
            $blocks = explode('\\', $this->test_class);
            $root = array_shift($blocks);
            $this->options['namespace'] = $root;
        }
        
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
        echo "File:\nfile://".$this->options['path_report']."index.html" ."\n";
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


    public function doCreateTestFiles($source, $dest)
    {
        $source = realpath($source).'/';
        $dest = realpath($dest).'/';
        
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
        \\LibCoverage\\LibCoverage::Begin({$InitClass}::class);
        
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
        
        \\LibCoverage\\LibCoverage::End();
    }
}

EOT;
        return $ret;
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
