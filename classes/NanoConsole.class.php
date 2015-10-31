<?php
/**
 * Nano console handler
 *
 * TODO: handle catchable fatals @see http://stackoverflow.com/questions/2468487/how-can-i-catch-a-catchable-fatal-error-on-php-type-hinting
 * TODO: tab completion @see http://php.net/manual/en/function.readline-completion-function.php
 */
class NanoConsole {

	/**
	 * Detect the environment
	 */
	function __construct() {
		$this->prompt = '> ';

		# set up readline
		$this->useReadline = function_exists("readline");
		$this->readLineHistoryFileName = sprintf('%s/.nano_history', getenv('HOME'));

		if ($this->useReadline && file_exists($this->readLineHistoryFileName)) {
			readline_read_history($this->readLineHistoryFileName);
		}
	}

	/**
	 * @return string
	 */
	public function banner() {
		/* @var NanoApp $app */
		global $app;

		$banner = <<<BANNER
nano v%s
PHP v%s
Readline support: %s

Host: %s
App directory: %s

The application instance is available as \$app variable.
BANNER;

		return sprintf( $banner,
			Nano::VERSION, phpversion(), ($this->useReadline ? 'yes' : 'no'),
			php_uname('n'), $app->getDirectory()
		);
	}

	public function loop() {
		$line = $this->readConsole($this->prompt);
		echo $this->execute($line);
	}

	/**
	 * @param $line string command to run
	 * @return string the result
	 */
	protected function execute( $line ) {
		/* @var NanoApp $app */
		global $app;

		# add trailing semicolon
		$line .= ';';

		ob_start();
		try {
			eval($line);
		}
		catch(Exception $e) {
			echo $e;
		}
		$ret = ob_get_clean();

		if ( $this->useReadline ) {
			readline_add_history( $line );
			readline_write_history($this->readLineHistoryFileName);
		}

		$ret .= "\n";
		return $ret;
	}

	/**
	 * @param string $prompt
	 * @return string
	 */
	protected function readConsole( $prompt = "" ) {
		if ( $this->useReadline ) {
			return readline( $prompt );
		} else {
			print $prompt;
			$fp = fopen( "php://stdin", "r" );
			$resp = trim( fgets( $fp, 1024 ) );
			fclose( $fp );
			return $resp;
		}
	}
}
