<?php

namespace PajuranCodes\Framework\Routing\ThrowablePresenter;

use Psr\Http\Message\ResponseFactoryInterface;
use PajuranCodes\Framework\Routing\ThrowablePresenter\ThrowablePresenterInterface;

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
     */
    public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory
    ) {
        
    }

}
