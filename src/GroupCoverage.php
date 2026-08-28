<?php declare(strict_types=1);
/**
 * LibCoverage
 * From this time on, you never be alone~
 */
namespace LibCoverage;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Selector as CodeCoverageSelector;
use SebastianBergmann\CodeCoverage\Filter as CodeCoverageFilter;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as ReportOfHtmlOfFacade;
use SebastianBergmann\CodeCoverage\Report\PHP as ReportOfPHP;

/**
 * 封装 php-code-coverage 的全部直接依赖(创建/采集/dump/合并/报告)。
 * 组件风格:_() 单例 + init() 初始化。
 * 按组(group)驱动覆盖率工作流:begin() 采集 -> end() 停止并 dump 到组目录 -> createReport()/showAllReport() 按组合并出报告。
 * 对外只暴露合并后的方法,避免调用方零散接触底层 API。
 */
class GroupCoverage
{
    public $options = [
    ];
    public $is_inited = false;

    protected $coverage;
    protected $current_path_dump = '';
    protected $current_name = '';
    protected $current_group = '';

    protected $is_end = false;

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
    public function __construct()
    {
    }

    /**
     * 
     * @param array<string,mixed> $options
     * @param ?object $context
     * @return static
     */
    public function init(array $options, ?object $context = null)
    {
        //$this->options = array_intersect_key(array_replace_recursive($this->options, $options) ?? [], $this->options);
        $this->coverage = $this->createCoverage();
        $this->is_inited = true;
        return $this;
    }
    public function getCoverage()
    {
        return $this->coverage;
    }
    /**
     * 开始采集：懒创建 coverage + 收录源码目录(options['path_src']) + start（内部防重入）。
     * 测试名与组名由参数传入（组名为空时回落到 options['group']），供 doEnd() 无参 dump 使用。
     */
    public function doBegin(string $name, string $group, string $path_src, string $path_dump): void
    {
        $this->is_end = false;
        $this->pre_begin($name, $group, $path_src, $path_dump);    // @codeCoverageIgnore
        LibCoverage::_()->doPause();
        $this->coverage->start($name);      // @codeCoverageIgnore
    }
    protected function pre_begin(string $name, string $group, string $path_src, string $path_dump): void
    {
        if (!$this->coverage) {
            $this->coverage = $this->createCoverage(); // @codeCoverageIgnore
        }
        $this->current_path_dump = $path_dump;
        $this->current_name = $name;
        $this->current_group = $group;

        $this->includePath($this->coverage, $path_src);
    }
    /**
     * 结束采集并 dump：stop + Report\PHP 序列化到 {path_dump}/{group}/{md5(name)}.php。
     * 路径/组/名来自 doBegin() 捕获的状态与 options['path_dump']。
     */
    public function doEnd(): void
    {
        if ($this->is_end) {
            return;
        }
        $this->coverage->stop();        // @codeCoverageIgnore
        LibCoverage::_()->doResume();   // @codeCoverageIgnore
        $this->post_end();
    }
    protected function post_end()
    {
        $path_dump = $this->current_path_dump;
        @mkdir($path_dump);
        $path_dump .= $this->current_group;
        @mkdir($path_dump);
        $file = (string)  $path_dump. DIRECTORY_SEPARATOR . \md5($this->current_name) . '.php';
        (new ReportOfPHP)->process($this->coverage, $file);
        $ext = "\n// ".$this->current_name ."\n";
        file_put_contents($file, $ext, FILE_APPEND);
        $this->is_end = true;
    }
    ////////////////////////////////////////////////////////////////////////////
    /**
     * 生成报告：新建 coverage 收录源码 -> 按组合并 dump -> 补全部分覆盖文件 -> 渲染 HTML 并统计。
     * $groups 为空时回落到 options['group']。
     * @param array<string>  $groups
     * @return array{lines_tested:int, lines_total:int, lines_percent:string}
     */
    public function createReport(array $groups, string $path_src, string $path_dump, string $path_report): array
    {
        $coverage = $this->createCoverage();
        $this->includePath($coverage, $path_src);
        $coverage->setTests([
          'T' => [
            'size' => 'unknown',
            'status' => -1,
          ],
        ]);
        foreach ($groups as $group) {
            $this->mergeFromDir($coverage, $path_dump.$group);
        }
        // 补全部分覆盖文件：未执行的可执行行加入 lineCoverage（空数组），
        // 否则报告只统计已执行行，部分覆盖文件会错误显示为 100%
        $this->fillPartialCoveredFiles($coverage);
        return $this->renderReport($coverage, $path_report);
    }
    /**
     * 创建 CodeCoverage。php-code-coverage 9.x 起必须显式传入 Driver + Filter
     */
    protected function createCoverage(): CodeCoverage
    {
        $filter = new CodeCoverageFilter();
        $driver = (new CodeCoverageSelector())->forLineCoverage($filter);
        return new CodeCoverage($driver, $filter);
    }
    /**
     * 把目录或文件加入 filter。9.x 用 includeDirectory;11.x 已移除,需展开目录(只收录 .php)
     */
    protected function includePath(CodeCoverage $coverage, string $path): void
    {
        $filter = $coverage->filter();
        $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = [];
        foreach ($iterator as $file) {
            if (is_file($file) && substr($file, -4) === '.php') {
                $files[] = $file;
            }
        }
        $filter->includeFiles($files);
    }
    protected function mergeFromDir(CodeCoverage $coverage, string $dir): void
    {
        if (!is_dir($dir)) {
            return; // @codeCoverageIgnore
        }
        $directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directory);
        $files = \iterator_to_array($iterator, false);
        foreach ($files as $file) {
            $t = include $file;
            $coverage->merge($t);
        }
    }
    /**
     * 补全部分覆盖文件：把 filter 内已有覆盖数据的文件的可执行行补进 lineCoverage（未执行的为空数组）。
     * php-code-coverage 9.x 只对"完全未覆盖"文件补未执行行（addUncoveredFilesFromFilter），
     * 部分覆盖文件若缺失未执行行，报告会把该文件错误统计为 100%。
     */
    protected function fillPartialCoveredFiles(CodeCoverage $coverage): void
    {
        $analyser = new \SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser(true, false);
        $lineCoverage = $coverage->getData()->lineCoverage();
        foreach ($coverage->filter()->files() as $file) {
            if (!isset($lineCoverage[$file])) {
                continue;    // @codeCoverageIgnore
            }
            foreach (array_keys($analyser->executableLinesIn($file)) as $line) {
                if (!isset($lineCoverage[$file][$line])) {
                    $lineCoverage[$file][$line] = [];  // @codeCoverageIgnore
                }
            }
        }
        $coverage->getData()->setLineCoverage($lineCoverage);
    }
    /**
     * 渲染 HTML 报告并返回行统计
     *
     * @return array{lines_tested:int, lines_total:int, lines_percent:string}
     */
    protected function renderReport(CodeCoverage $coverage, string $path_report): array
    {
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
}
