<?php declare(strict_types=1);
/**
 * DuckPhp
 * From this time, you never be alone~
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
class GroupCoverageRunner
{
    public $options = [
        'path'  => '',
        'path_src' => 'src/',
        'path_dump' => 'test_coveragedumps',
        'path_report' => 'test_reports',
        'groups' => [],
    ];
    public $is_inited = false;

    protected $coverage;
    protected $current_name = '';
    protected $current_group = '';

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
    protected static function IsAbsPath($path)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            // Linux
            return substr($path, 0, 1) === '/'; // @codeCoverageIgnore
        }
        // Windows
        return (bool) preg_match('/^([a-zA-Z]:[\\\\\/]?|\\\\\\\\)/', $path); // @codeCoverageIgnore
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

    /**
     * @return static
     */
    public function init(array $options, ?object $context = null)
    {
        $this->options = array_intersect_key(array_replace_recursive($this->options, $options) ?? [], $this->options);
        $this->coverage = $this->createCoverage();
        $this->is_inited = true;
        $this->options['path'] = realpath($this->options['path']).DIRECTORY_SEPARATOR;
        $path_dump = $this->getComponenetPathByKey('path_dump');
        @mkdir($path_dump);
        $path_report = $this->getComponenetPathByKey('path_report');
        @mkdir($path_report);
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
    public function doBegin(string $name, string $group = ''): void
    {
        $this->pre_begin($name, $group);    // @codeCoverageIgnore
        $this->coverage->start($name);      // @codeCoverageIgnore
    }
    protected function pre_begin(string $name, string $group = ''): void
    {
        if (!$this->coverage) {
            $this->coverage = $this->createCoverage();
        }
        $this->current_name = $name;
        $this->current_group = ($group !== '') ? $group : (string) ($this->options['group'] ?? '');

        $this->includePath($this->coverage, (string) $this->options['path_src']);
    }
    /**
     * 结束采集并 dump：stop + Report\PHP 序列化到 {path_dump}/{group}/{md5(name)}.php。
     * 路径/组/名来自 doBegin() 捕获的状态与 options['path_dump']。
     */
    public function doEnd(): void
    {
        $this->coverage->stop(); // @codeCoverageIgnore
        $this->post_end();// @codeCoverageIgnore
    }
    protected function post_end()
    {
        $path_dump = $this->getComponenetPathByKey('path_dump');
        $path_dump .= $this->current_group;
        @mkdir($path_dump);
        $file = (string)  $path_dump. DIRECTORY_SEPARATOR . \md5($this->current_name) . '.php';
        (new ReportOfPHP)->process($this->coverage, $file);
    }
    /**
     * 生成报告：新建 coverage 收录源码 -> 按组合并 dump -> 补全部分覆盖文件 -> 渲染 HTML 并统计。
     * $groups 为空时回落到 options['group']。
     *
     * @return array{lines_tested:int, lines_total:int, lines_percent:string}
     */
    public function createReport(string $path_src, array $groups, string $path_dump, string $path_report): array
    {
        if (empty($groups)) {
            $groups = [(string) ($this->options['group'] ?? '')];
        }
        $coverage = $this->createCoverage();
        $this->includePath($coverage, $path_src);
        $coverage->setTests([
          'T' => [
            'size' => 'unknown',
            'status' => -1,
          ],
        ]);
        foreach ($groups as $group) {
            $path_dump = $this->getComponenetPathByKey('path_dump').$group;
            $this->mergeFromDir($coverage, $path_dump . $group);
        }
        // 补全部分覆盖文件：未执行的可执行行加入 lineCoverage（空数组），
        // 否则报告只统计已执行行，部分覆盖文件会错误显示为 100%
        $this->fillPartialCoveredFiles($coverage);
        $path_report = $this->getComponenetPathByKey('path_report');
        return $this->renderReport($coverage, $path_report);
    }
    /**
     * createReport() + 打印展示（对齐 LibCoverage 风格：Output File / Test Lines）。
     * 参数从 options 读取（path_src/path_dump/path_report/groups）。
     *
     * @return array{lines_tested:int, lines_total:int, lines_percent:string}
     */
    public function showAllReport(): array
    {
        // 准备废弃
        $data = $this->createReport(
            (string) $this->options['path_src'],
            (array) ($this->options['groups'] ?? []),
            (string) $this->options['path_dump'],
            (string) $this->options['path_report']
        );
        $path_report = $this->getComponenetPathByKey('path_dump');
        echo "\nSTART CREATE REPORT AT " . DATE(DATE_ATOM) . "\n";
        echo "Output File:\n\n\033[42;30mfile://" .$path_report. "/index.html" . "\033[0m\n";
        echo "\n\033[42;30m All Done \033[0m Test Done!";
        echo "\nTest Lines: \033[42;30m{$data['lines_tested']}/{$data['lines_total']}({$data['lines_percent']})\033[0m\n";
        echo "\n\n";
        return $data;
    }
    /**
     * 创建 CodeCoverage。php-code-coverage 9.x 起必须显式传入 Driver + Filter
     */
    protected static function createCoverage(): CodeCoverage
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
        //TODO 不是 dump 文件，还要和ID
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
