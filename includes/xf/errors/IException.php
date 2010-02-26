<?php
/**
 * This function is for versions of PHP older than 5.3
 * Creates an alias named alias based on the defined class original.
 * The aliased class is exactly the same as the original class.
 *
 * @author     Paul Kotets <paul.kotets@gmail.com>
 * @link       http://www.php.net/manual/en/function.class-alias.php#93327
 */
if (!function_exists('class_alias')) {
	function class_alias($original, $alias) {
		eval('class ' . $alias . ' extends ' . $original . ' {}');
	}
}

/*class Exception
{
    protected $message = 'Unknown exception';   // exception message
    private   $string;                          // __toString cache
    protected $code = 0;                        // user defined exception code
    protected $file;                            // source filename of exception
    protected $line;                            // source line of exception
    private   $trace;                           // backtrace
    private   $previous;                        // previous exception if nested exception

    public function __construct($message = null, $code = 0, Exception $previous = null);

    final private function __clone();           // Inhibits cloning of exceptions.

    final public  function getMessage();        // message of exception
    final public  function getCode();           // code of exception
    final public  function getFile();           // source filename
    final public  function getLine();           // source line
    final public  function getTrace();          // an array of the backtrace()
    final public  function getPrevious();       // previous exception
    final public  function getTraceAsString();  // formatted string of trace

    public function __toString();               // formatted string for display
}*/

/**
 * Simple interface to remember all attributes of the builtin php Exception class.
 *
 * @since xf 1.0.0
 * @author Jim Isaacs <jimpisaacs@gmail.com>
 * @package xf
 * @subpackage errors
 */
interface xf_IException
{
    /* Protected methods inherited from Exception class */
    public function getMessage();                 // Exception message 
    public function getCode();                    // User-defined Exception code
    public function getFile();                    // Source filename
    public function getLine();                    // Source line
    public function getTrace();                   // An array of the backtrace()
    public function getTraceAsString();           // Formated string of trace
    
    /* Overrideable methods inherited from Exception class */
    public function __toString();                 // formated string for display
    //public function __construct( $message = null, $code = 0 );
}
?>