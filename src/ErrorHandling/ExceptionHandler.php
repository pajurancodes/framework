<?php

namespace PajuranCodes\Framework\ErrorHandling;

use PajuranCodes\{
    Http\Message\Emitter\ResponseEmitterInterface,
    Framework\ErrorHandling\ThrowablePresenter\ThrowablePresenterInterface,
};

/**
 * This class can be used as a user-defined exception handler function set by the 
 * error handling function set_exception_handler(). In this role, it is responsible 
 * for handling all exceptions.
 * 
 * @link https://www.php.net/manual/en/function.set-exception-handler.php Error Handling Functions: set_exception_handler
 * @link https://www.php.net/manual/en/function.restore-exception-handler.php Error Handling Functions: restore_exception_handler
 * 
 * @author pajurancodes
 */
class ExceptionHandler {

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
     * Handle all exceptions.
     * 
     * @param \Throwable $throwable A throwable to handle. It can be an error or an exception.
     * @return void
     */
    public function __invoke(\Throwable $throwable): void {
        $response = $this->throwablePresenter->present($throwable);

        $this->responseEmitter->emit($response);
    }

}
