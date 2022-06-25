<?php

namespace PajuranCodes\Framework\ErrorHandling\ThrowablePresenter;

use function error_log;
use Psr\Http\Message\ResponseFactoryInterface;
use PajuranCodes\Framework\ErrorHandling\ThrowablePresenter\ThrowablePresenterInterface;

/**
 * A throwable presenter.
 * 
 * This component is used to present any object 
 * that can be thrown via a throw statement, 
 * including Error and Exception, to the user.
 * 
 * @author pajurancodes
 */
abstract class ThrowablePresenter implements ThrowablePresenterInterface {

    /**
     * 
     * @param ResponseFactoryInterface $responseFactory A response factory.
     * @param bool $debugEnabled A flag to indicate if the debug mode 
     * of the application is active or not.
     */
    public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly bool $debugEnabled
    ) {
        
    }

    /**
     * Send a throwable to PHP's system logger.
     * 
     * The logging destination depends on what the 
     * error_log configuration directive is set to.
     * 
     * @link https://www.php.net/manual/en/function.error-log.php Error Handling Functions: error_log
     * 
     * @param \Throwable $throwable A throwable.
     * @return void
     */
    protected function logThrowableToSystemLogger(\Throwable $throwable): void {
        error_log((string) $throwable);
    }

}
