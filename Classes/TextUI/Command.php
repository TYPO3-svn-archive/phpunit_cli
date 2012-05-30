<?php 

require_once('PHPUnit/TextUI/Command.php');

class Tx_PhpunitCli_TextUI_Command extends PHPUnit_TextUI_Command {

	/**
	 * @param PHPUnit_Framework_Test $suite
	 */
	public function setTest(PHPUnit_Framework_Test $test) {
		$this->arguments['test'] = $test;
	}
}

?>