<?php

namespace PajuranCodes\Framework\Routing;

use PajuranCodes\Router\RouteInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * An interface to a route invoker.
 *
 * @author pajurancodes
 */
interface RouteInvokerInterface {

    /**
     * Invoke a route.
     *
     * @param RouteInterface $route A route.
     * @return ResponseInterface The response to the current request.
     */
    public function invoke(RouteInterface $route): ResponseInterface;
}
