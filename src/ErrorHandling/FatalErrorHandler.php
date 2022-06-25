<?php

namespace PajuranCodes\Framework\ErrorHandling;

use const E_ERROR;
use const E_PARSE;
use const E_CORE_ERROR;
use const E_CORE_WARNING;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use function in_array;
use function error_get_last;
use PajuranCodes\{
    Http\Message\Emitter\ResponseEmitterInterface,
    Framework\ErrorHandling\ThrowablePresenter\ThrowablePresenterInterface,
};

/**
 * This class can be used as a user-defined function set by the function 
 * handling function register_shutdown_function(). In this role, it is 
 * responsible for handling all fatal errors.
 * 
 * Fatal errors are those error types which can not be handled with 
 * a user-defined function set by an error handling function (like 
 * set_error_handler(), for example):
 * 
 *  - E_ERROR
 *  - E_PARSE
 *  - E_CORE_ERROR
 *  - E_CORE_WARNING
 *  - E_COMPILE_ERROR
 *  - E_COMPILE_WARNING
 * 
 * @link https://www.php.net/manual/en/function.register-shutdown-function.php register_shutdown_function
 * @link https://www.php.net/manual/en/function.set-error-handler.php#refsect1-function.set-error-handler-description set_error_handler > Description
 * @link https://www.php.net/manual/en/errorfunc.constants.php Error Handling and Logging > Predefined Constants
 * 
 * @author pajurancodes
 */
class FatalErrorHandler {

    /**
     * A list of fatal error types needed to be handled.
     * 
     * @link https://www.php.net/manual/en/function.set-error-handler.php#refsect1-function.set-error-handler-description set_error_handler > Description
     * @link https://www.php.net/manual/en/errorfunc.constants.php Error Handling and Logging > Predefined Constants
     * 
     * @var int[]
     */
    private const FATAL_ERRORS = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
    ];

    /**
     * 
     * @param ResponseEmitterInterface $responseEmitter A response emitter.
     * @param ThrowablePresenterInterface $throwablePresenter A throwable presenter.
     */
    public function __construct(
        private readonly ResponseEmitterInterface $responseEmitter,
        private readonly ThrowablePresenterInterface $throwablePresenter
    ) {
        
    }

    /**
     * Handle all fatal errors.
     * 
     * This method is executed on shutdown, e.g. after 
     * script execution finishes or exit() is called.
     * 
     * Execution stops after this method is called.
     * 
     * @return void
     * @throws \ErrorException The last occurred error.
     */
    public function __invoke(): void {
        $lastError = error_get_last();

        error_clear_last();

        if (
            isset($lastError) &&
            $lastError &&
            in_array($lastError['type'], self::FATAL_ERRORS, true)
        ) {
            try {
                throw new \ErrorException(
                        $lastError['message'],
                        0,
                        $lastError['type'],
                        $lastError['file'],
                        $lastError['line']
                );
            } catch (\Throwable $throwable) {
                $response = $this->throwablePresenter->present($throwable);

                $this->responseEmitter->emit($response);
            }
        }

        /*
         * Completely stop processing, so that no other 
         * registered shutdown functions will be called.
         */
        exit();
    }

}
