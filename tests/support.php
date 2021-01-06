<?php
require_once('bootstrap.php');

class support extends \PHPUnit\Framework\TestCase
{
    public function testMain()
    {
        LibCoverage\LibCoverage::G()->showAllReport();
    }
}
