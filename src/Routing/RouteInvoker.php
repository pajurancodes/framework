<?php

namespace PajuranCodes\Framework\Routing;

use function is_array;
use function is_string;
use function array_key_exists;
use PajuranCodes\{
    Router\RouteInterface,
    Framework\Routing\RouteInvokerInterface,
};
use Psr\Http\Message\ResponseInterface;
use Invoker\InvokerInterface as CallableInvokerInterface;

/**
 * A route invoker.
 *
 * @author pajurancodes
 */
class RouteInvoker implements RouteInvokerInterface {

    /**
     * A valid key of a route handler.
     * 
     * @var string
     */
    private const ROUTE_HANDLER_KEY_CONTROLLER = 'controller';

    /**
     * A valid key of a route handler.
     * 
     * @var string
     */
    private const ROUTE_HANDLER_KEY_VIEW = 'view';

    /**
     *
     * @param CallableInvokerInterface $callableInvoker A callable invoker.
     */
    public function __construct(
        private readonly CallableInvokerInterface $callableInvoker
    ) {
        
    }

    /**
     * @inheritDoc
     */
    public function invoke(RouteInterface $route): ResponseInterface {
        return $this->routeHasAssociativeArrayHandler($route) ?
            $this->invokeRouteWithAssociativeArrayHandler($route) :
            $this->invokeRouteWithCallableHandler($route)
        ;
    }

    /**
     * Check if a route has an associative array as handler.
     * 
     * {@internal This method checks if the route handler 
     * is an array and has at least one string key,
     * therefore beeing associative.}
     *
     * @param RouteInterface $route A route.
     * @return bool True if the route has an associative array as handler, or false otherwise.
     */
    private function routeHasAssociativeArrayHandler(RouteInterface $route): bool {
        $handler = $route->getHandler();

        if (is_array($handler)) {
            foreach ($handler as $key => $value) {
                if (is_string($key)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Invoke a route with an associative array as route handler.
     * 
     * The route handler MUST be of type (string|array|object)[]
     * and MUST contain two elements.
     * 
     * The element with the key "controller" MUST have a value 
     * of type string|array|object and CAN return anything, e.g. 
     * a value of type mixed, including null.
     * 
     * The element with the key "view" MUST have a value of type 
     * string|array|object and MUST return an instance of 
     * "Psr\Http\Message\ResponseInterface".
     *
     * @param RouteInterface $route A route.
     * @return ResponseInterface The response to the current request.
     */
    private function invokeRouteWithAssociativeArrayHandler(RouteInterface $route): ResponseInterface {
        $controller = $this->resolveController($route);
        $view = $this->resolveView($route);

        $parameters = $route->getParameters();

        $this->callableInvoker->call($controller, $parameters);

        return $this->callableInvoker->call($view, $parameters);
    }

    /**
     * Resolve the controller from a route handler.
     *
     * @param RouteInterface $route A route.
     * @return string|array|object The callable value.
     * @throws \UnexpectedValueException No key "controller" found or no callable value set.
     */
    private function resolveController(RouteInterface $route): string|array|object {
        $handler = $route->getHandler();

        $controller = array_key_exists(self::ROUTE_HANDLER_KEY_CONTROLLER, $handler) ?
            $handler[self::ROUTE_HANDLER_KEY_CONTROLLER] :
            null
        ;

        if (!isset($controller)) {
            throw new \UnexpectedValueException(
                    'The route handler of the route "' . $route->getPattern() . '" '
                    . 'must have a key "' . self::ROUTE_HANDLER_KEY_CONTROLLER . '" '
                    . 'and a value of type string, array, or object assigned to it.'
            );
        }

        return $controller;
    }

    /**
     * Resolve the view from a route handler.
     *
     * @param RouteInterface $route A route.
     * @return string|array|object The callable value.
     * @throws \UnexpectedValueException No key "view" found or no callable value set.
     */
    private function resolveView(RouteInterface $route): string|array|object {
        $handler = $route->getHandler();

        $view = array_key_exists(self::ROUTE_HANDLER_KEY_VIEW, $handler) ?
            $handler[self::ROUTE_HANDLER_KEY_VIEW] :
            null
        ;

        if (!isset($view)) {
            throw new \UnexpectedValueException(
                    'The route handler of the route "' . $route->getPattern() . '" '
                    . 'must have a key "' . self::ROUTE_HANDLER_KEY_VIEW . '" '
                    . 'and a value of type string, array, or object assigned to it.'
            );
        }

        return $view;
    }

    /**
     * Invoke a route with a callable as route handler.
     *
     * @param RouteInterface $route A route.
     * @return ResponseInterface The response to the current request.
     */
    private function invokeRouteWithCallableHandler(RouteInterface $route): ResponseInterface {
        $handler = $route->getHandler();
        $parameters = $route->getParameters();

        return $this->callableInvoker->call($handler, $parameters);
    }

}
