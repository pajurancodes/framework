<?php

namespace PajuranCodes\Framework\ErrorHandling;

/**
 * This class can be used as a user-defined error handler function set by the 
 * error handling function set_error_handler(). In this role, it is responsible 
 * for handling all errors, except the fatal ones.
 * 
 * The types of fatal errors which are not handled by this class are:
 * 
 *  - E_ERROR
 *  - E_PARSE
 *  - E_CORE_ERROR
 *  - E_CORE_WARNING
 *  - E_COMPILE_ERROR
 *  - E_COMPILE_WARNING
 * 
 * @link https://www.php.net/manual/en/function.set-error-handler.php Error Handling Functions: set_error_handler
 * @link https://www.php.net/manual/en/function.restore-error-handler.php Error Handling Functions: restore_error_handler
 * 
 * @author pajurancodes
 */
class ErrorHandler {

    /**
     * 
     * @link https://www.php.net/manual/en/function.error-reporting.php Error Handling Functions: error_reporting
     * @link https://www.php.net/manual/en/errorfunc.configuration.php#ini.error-reporting Runtime Configuration: error_reporting
     * 
     * @param int $errorReportingLevel An error reporting level. Either an integer 
     * representing a bit field, or named constants.
     */
    public function __construct(
        private readonly int $errorReportingLevel
    ) {
        
    }

    /**
     * Handle all errors, except the fatal ones.
     * 
     * If the error-handler function returns (e.g. returns false), 
     * script execution will continue with the next statement after 
     * the one that caused an error.
     * 
     * @link https://www.php.net/manual/en/function.set-error-handler.php Error Handling Functions: set_error_handler
     * 
     * @param int $errno The level of the error raised.
     * @param string $errstr The error message.
     * @param string $errfile The filename that the error was raised in.
     * @param int $errline The line number where the error was raised.
     * @return bool
     * @throws \ErrorException An error exception thrown instead of the raised error.
     */
    public function __invoke(int $errno, string $errstr, string $errfile, int $errline): bool {
        /*
         * If the code of the currently raised error is not 
         * included in error_reporting, the error will be let 
         * to fall through to the standard PHP error handler.
         */
        if (!($this->errorReportingLevel & $errno)) {
            return false;
        }

        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

}
