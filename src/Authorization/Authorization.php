<?php

namespace PajuranCodes\Framework\Authorization;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
    ResponseFactoryInterface,
};
use Psr\Http\Server\{
    MiddlewareInterface,
    RequestHandlerInterface,
};

/**
 * An authorization middleware.
 * 
 * This component is needed to authorize the request.
 * 
 * @author pajurancodes
 */
class Authorization implements MiddlewareInterface {

    /**
     * 
     * @param ResponseFactoryInterface $responseFactory A response factory.
     * @param bool $needsAuthorization (optional) A flag to indicate if the request 
     * needs authorization or not.
     */
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly bool $needsAuthorization = true
    ) {
        
    }

    /**
     * @inheritDoc
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // No authorization needed: handle the next request handler.
        if (!$this->needsAuthorization) {
            return $handler->handle($request);
        }

        // Read from db, for example.
        $authorized = false;

        // Authorization needed and not authorized: return a "not authorized" response.
        if (!$authorized) {
            $response = $this->responseFactory->createResponse();

            $response->getBody()->write(
                'Hello from "PajuranCodes\Framework\Authorization". '
                . 'You are not authorized to proceed further.'
            );

            return $response;
        }

        /*
         * Authorization needed and authorized: handle the 
         * next request handler. Optionally, a "new" request 
         * can be provided by manipulating the given request.
         * If needed, transform the returned response (for 
         * example by gzip-compressing its body).
         */
        $request = $request->withAttribute('authorized', true);
        $response = $handler->handle($request);

        $response->getBody()->write(
            'Hello from "PajuranCodes\Framework\Authorization". '
            . 'This is a transformed response body.');

        return $response;
    }

}
