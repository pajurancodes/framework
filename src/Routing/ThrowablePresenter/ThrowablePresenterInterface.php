<?php

namespace PajuranCodes\Framework\Routing\ThrowablePresenter;

use Psr\Http\Message\ResponseInterface;

/**
 * An interface to a throwable presenter.
 * 
 * @author pajurancodes
 */
interface ThrowablePresenterInterface {

    /**
     * Present a throwable.
     * 
     * @param \Throwable $throwable A throwable.
     * @return ResponseInterface The response to the current request.
     */
    public function present(\Throwable $throwable): ResponseInterface;
}
