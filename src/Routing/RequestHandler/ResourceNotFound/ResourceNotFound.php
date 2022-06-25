<?php

namespace PajuranCodes\Framework\Routing\RequestHandler\ResourceNotFound;

use Psr\Http\{
    Server\RequestHandlerInterface,
    Message\ResponseFactoryInterface,
};

/**
 * A request handler indicating that a
 * specified resource could not be found.
 * 
 * @author pajurancodes
 */
abstract class ResourceNotFound implements RequestHandlerInterface {

    /**
     * 
     * @param ResponseFactoryInterface $responseFactory A response factory.
     */
    public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory
    ) {
        
    }

}
