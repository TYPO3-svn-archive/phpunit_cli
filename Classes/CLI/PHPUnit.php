<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

require_once(t3lib_extMgm::extPath('phpunit_cli') . 'Classes/TextUI/Command.php');

/**
 * Class Tx_PhpunitCli_CLI_PHPUnit for the "phpunit_cli" extension.
 *
 * This class runs PHPUnit in CLI mode.
 *
 * @package TYPO3
 * @subpackage tx_phpunitcli
 *
 * @author Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
 */
class Tx_PhpunitCli_CLI_PHPUnit extends t3lib_cli {
	/**
	 * @var string
	 */
	protected $customArgumentKey = '--typo3-extensions';
	
	/**
	 * same as class name
	 *
	 * @var string
	 */
	protected $prefixId = 'Tx_PhpunitCli_CLI_PHPUnit';

	/**
	 * path to this script relative to the extension dir
	 *
	 * @var string
	 */
	protected $scriptRelPath = 'Classes/CLI/PHPUnit.php';

	/**
	 * definition of the extension name
	 *
	 * @var string
	 */
	protected $extKey = 'phpunitcli_cli';

	/**
	 * The constructor.
	 */
	public function __construct() {
		parent::t3lib_cli();
		$this->cli_options = array_merge($this->cli_options, array());
		$this->cli_help = array_merge($this->cli_help, array(
			'name' => 'phpunit_cli',
			'synopsis' => $this->extKey . ' command [clientId] ###OPTIONS###',
			'description' => 'This script can run PHPUnit tests on multiple TYPO3 extensions at the same time while preserving all of the command line phpunit options',
			'examples' => 'php .../typo3/cli_dispatch.phpsh --typo3-extensions=fluid,extbase',
			'author' => '(c) 2012 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>',
		));
	}

	/**
	 * Detects the action and calls the related methods.
	 *
	 * @param array $argv array contains the arguments, which were post via CLI
	 */
	public function cli_main() {		
		$command = new Tx_PHPUnitCli_TextUI_Command();
		
		$arguments = $this->cli_getArgIndex();
		$rawArguments = $_SERVER['argv'];
		
		if ($arguments[$this->customArgumentKey]) {
			$extensionsWithTestSuites = $this->getExtensionsWithTestSuites();
			$testSuite = new PHPUnit_Framework_TestSuite();
			
			if (count($arguments[$this->customArgumentKey])==1) {
					// parameter as comma separated list of extension keys
				$extensionKeysToProcess = t3lib_div::trimExplode(',', $arguments[$this->customArgumentKey][0]);
			} else {
					// parameter
				$extensionKeysToProcess = $arguments[$this->customArgumentKey];
			}
				// Load the files containing test cases from extensions:
			foreach ($extensionKeysToProcess as $extensionKey) {
				if (!t3lib_extMgm::isLoaded($extensionKey)) {
					$this->cli_echo("Extension $extensionKey not loaded\n");
					exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
				}
				$paths = $extensionsWithTestSuites[$extensionKey];
				self::loadRequiredTestClasses($paths);
			}
				
				// Add all classes to the test suite which end with "testcase"
			foreach (get_declared_classes() as $class) {
				$classReflection = new ReflectionClass($class);
				if ($classReflection->isSubclassOf('tx_phpunit_testcase') && (strtolower(substr($class, -8, 8)) == 'testcase' || substr($class, -4, 4) == 'Test') && $class != 'tx_phpunit_testcase' && $class != 'tx_phpunit_database_testcase' && $class != 'tx_t3unit_testcase') {
					$testSuite->addTestSuite($class);
				}
			}
			
			$command->setTest($testSuite);
			
			$rawArguments = $this->filterCustomCLIArguments($rawArguments);
		}

		$command->run($rawArguments, true);
		
		$testRunner = new PHPUnit_TextUI_TestRunner();

		$result = $testRunner->doRun($testSuite, $arguments = array());
		
		if ($exit) {
			if (isset($result) && $result->wasSuccessful()) {
				exit(PHPUnit_TextUI_TestRunner::SUCCESS_EXIT);
			}
		
			else if (!isset($result) || $result->errorCount() > 0) {
				exit(PHPUnit_TextUI_TestRunner::EXCEPTION_EXIT);
			}
		
			else {
				exit(PHPUnit_TextUI_TestRunner::FAILURE_EXIT);
			}
		}		
	}
	
	/**
	 * @param array $arguments
	 */
	protected function filterCustomCLIArguments($arguments) {
		$removeNext = false;
		foreach ($arguments as $k => $a) {
			if ($removeNext) {
				if (strpos($a, '-')===0) {
					$removeNext = false;
					break;
				} else {
					unset($arguments[$k]);
				}
			}
			if (strpos($a, $this->customArgumentKey)===0) {
				$removeNext = true;
				unset($arguments[$k]);
			}
		}
		$arguments = array_values($arguments);

		return $arguments;
	}
	
	/**
	 * @param array $paths
	 */
	protected static function loadRequiredTestClasses ($paths) {
		if (isset($paths)) {
			foreach ($paths as $path => $fileNames) {
				foreach ($fileNames as $fileName) {
					require_once (realpath($path.'/'.$fileName));
				}
			}
		}
	}

	/**
	 * Scans all available extensions for test suites and returns the path / file names in an array
	 *
	 * @return	array		Array of testcase files
	 */
	protected function getExtensionsWithTestSuites () {
			// Fetch extension manager configuration options
		$excludeExtensions = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['excludeextensions']);
		$outOfLineTestCases = $this->traversePathForTestCases($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['phpunit']['outoflinetestspath']);
	
			// Get list of loaded extensions
		$extList = explode(',', $GLOBALS['TYPO3_CONF_VARS']['EXT']['extList']);
	
		$extensionsOwnTestCases = array();
		foreach ($extList as $extKey) {
			$extPath = t3lib_extMgm::extPath($extKey);
			if (is_dir($extPath . 'Tests/')) {
				$testCasesDirectory = $extPath . 'Tests/';
			} else {
				$testCasesDirectory = $extPath . 'tests/';
			}
	
			$testCasesArr = $this->findTestCasesInDir($testCasesDirectory);
			if (!empty($testCasesArr)) {
				$extensionsOwnTestCases[$extKey] = $testCasesArr;
			}
		}
	
		$coreTestCases = array();
		$coreTestsDirectory = file_exists(PATH_site . 'tests/') ? PATH_site . 'tests/' : PATH_site . 'typo3_src/tests/';
		if (@is_dir($coreTestsDirectory)) {
			$coreTestCases['typo3'] = $this->findTestCasesInDir(
					$coreTestsDirectory
			);
		}
	
		$totalTestsArr = array_merge_recursive(
				$outOfLineTestCases, $extensionsOwnTestCases, $coreTestCases
		);
	
			// Exclude extensions according to extension manager config
		$returnTestsArr = array_diff_key($totalTestsArr, array_flip($excludeExtensions));
		return $returnTestsArr;
	}
	
	/**
	 * Traverses a given path recursively for *testcase.php files
	 *
	 * @param	string		$path: The path to descent from
	 * @return	array		Array of paths / filenames
	 */
	protected function traversePathForTestCases($path) {
		if (!is_dir($path)) {
			return array();
		}
	
		$extensionsArr = array();
		$dirs = t3lib_div::get_dirs($path);
		if (is_array($dirs)) {
			sort($dirs);
			foreach ($dirs as $dirName) {
				if ($this->isExtensionLoaded($dirName)) {
					$testsPath = $path . $dirName . '/tests/';
					$extensionsArr[$dirName] = $this->findTestCasesInDir($testsPath);
				}
			}
		}
	
		return $extensionsArr;
	}
	
	/**
	 * Recursively finds all test case files in the directory $dir.
	 *
	 * @param string the absolute path of the directory in which to look for
	 *               test cases
	 * @return array files names of the test cases in the directory $dir and all
	 *               its subdirectories relative to $dir, will be empty if no
	 *               test cases have been found
	 */
	protected function findTestCasesInDir($dir) {
		if (!is_dir($dir)) {
			return array();
		}
	
		$pathLength = strlen($dir);
		$fileNames = t3lib_div::getAllFilesAndFoldersInPath(
				array(), $dir, 'php'
		);
	
		$testCaseFileNames = array ();
		foreach ($fileNames as $fileName) {
			if ((substr($fileName, -12) === 'testcase.php')
					|| (substr($fileName, -8) === 'Test.php')
			) {
				$testCaseFileNames[] = substr($fileName, $pathLength);
			}
		}
	
		$extensionsArr = array();
		if (!empty($testCaseFileNames)) {
			sort($testCaseFileNames);
			$extensionsArr[$dir] = $testCaseFileNames;
		}
	
		return $extensionsArr;
	}
	
	/**
	 * Returns the localized string for the key $key.
	 *
	 * @param string $key The key of the string to retrieve, must not be empty
	 * @param string $default OPTIONAL default language value
	 * @return string the localized string for the key $key
	 */
	protected function getLL($key, $default = '') {
		return $GLOBALS['LANG']->getLL($key, $default);
	}	
}

if (defined('TYPO3_cliMode')) {
		/* @var $phpunit Tx_PhpunitCli_CLI_PHPUnit */
	$phpunit = t3lib_div::makeInstance('Tx_PhpunitCli_CLI_PHPUnit'); 
	$phpunit->cli_main();
}

?>