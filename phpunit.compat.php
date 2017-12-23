<?php

// Replace PHPUnit forward compatbility with class aliases to remove method signature errors in PHP 7.x
if ( ! class_exists('PHPUnit\\Framework\\DataProviderTestSuite')) {
	class_alias('PHPUnit_Framework_Assert', 'PHPUnit\\Framework\\Assert');
	class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\\Framework\\AssertionFailedError');
	class_alias('PHPUnit_Framework_BaseTestListener', 'PHPUnit\\Framework\\BaseTestListener');
	class_alias('PHPUnit_Framework_IncompleteTestCase', 'PHPUnit\\Framework\\IncompleteTestCase');
	class_alias('PHPUnit_Framework_SkippedTestCase', 'PHPUnit\\Framework\\SkippedTestCase');
	class_alias('PHPUnit_Framework_Test', 'PHPUnit\\Framework\\Test');
	class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\\Framework\\TestCase');
	class_alias('PHPUnit_Framework_TestSuite', 'PHPUnit\\Framework\\TestSuite');
	class_alias('PHPUnit_Framework_TestSuite_DataProvider', 'PHPUnit\\Framework\\DataProviderTestSuite');
	class_alias('PHPUnit_Framework_WarningTestCase', 'PHPUnit\\Framework\\WarningTestCase');
	class_alias('PHPUnit_Util_Test', 'PHPUnit\\Util\\Test');
}
