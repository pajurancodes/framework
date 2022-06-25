<?php

namespace PajuranCodes\Framework\Routing;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
use Psr\Http\Server\{
    MiddlewareInterface,
    RequestHandlerInterface,
};
use PajuranCodes\Router\{
    Route,
    RouteInterface,
    RouterInterface,
    Exception\RouteNotFound,
    Exception\HttpMethodNotAllowed,
};
use PajuranCodes\Framework\Routing\ThrowablePresenter\ThrowablePresenterInterface;

/**
 * A routing middleware.
 * 
 * @link https://www.php-fig.org/psr/psr-15/meta/#reusable-middleware-examples Reusable Middleware Examples
 * 
 * @author pajurancodes
 */
class Router implements MiddlewareInterface {

    /**
     * 
     * @param RouterInterface $router A router.
     * @param ThrowablePresenterInterface $presenterForRouteNotFoundException A throwable presenter 
     * to present an exception indicating that no route was found.
     * @param ThrowablePresenterInterface $presenterForHttpMethodNotAllowedException A throwable presenter
     * to present an exception indicating that the HTTP method of the request is not supported.
     * @param string $requestAttributeForSavingMatchedRoute (optional) The name of a request 
     * attribute under which a matched route should be saved into the request instance.
     */
    public function __construct(
        private readonly RouterInterface $router,
        private readonly ThrowablePresenterInterface $presenterForRouteNotFoundException,
        private readonly ThrowablePresenterInterface $presenterForHttpMethodNotAllowedException,
        private readonly string $requestAttributeForSavingMatchedRoute = Route::class
    ) {
        
    }

    /**
     * Process an incoming server request.
     * 
     * This method matches the request to a collection of routes, 
     * saves the matched route and its route parameters as values 
     * of request attributes and delegates further processing to 
     * the given request handler.
     * 
     * {@internal In case a route is not found, or the HTTP 
     * method of the request is not supported, the corresponding 
     * exception is passed to a throwable presenter, in order to 
     * be presented to the user.}
     * 
     * @param ServerRequestInterface $request A server request.
     * @param RequestHandlerInterface $handler A request handler.
     * @return ResponseInterface The response to the current request.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            $matchedRoute = $this->matchRequestToRouteCollection($request);

            $request = $this->saveRouteToRequest($request, $matchedRoute);
            $request = $this->saveRouteParametersToRequest($request, $matchedRoute);

            return $handler->handle($request);
        } catch (RouteNotFound $exception) {
            return $this->presenterForRouteNotFoundException->present($exception);
        } catch (HttpMethodNotAllowed $exception) {
            return $this->presenterForHttpMethodNotAllowedException->present($exception);
        }
    }

    /**
     * Match the components of a request to the 
     * ones of each route in a collection of routes.
     * 
     * @param ServerRequestInterface $request A server request.
     * @return RouteInterface The matched route, if found.
     */
    private function matchRequestToRouteCollection(ServerRequestInterface $request): RouteInterface {
        return $this->router->match(
                $request->getMethod(),
                $request->getUri()->getPath()
        );
    }

    /**
     * Save a route as attribute of the given request instance.
     * 
     * @param ServerRequestInterface $request A server request.
     * @param RouteInterface $route A route.
     * @return ServerRequestInterface The server request with the saved route.
     */
    private function saveRouteToRequest(
        ServerRequestInterface $request,
        RouteInterface $route
    ): ServerRequestInterface {
        return $request->withAttribute(
                $this->requestAttributeForSavingMatchedRoute,
                $route
        );
    }

    /**
     * Save the the list of route parameters of a route
     * as attributes of the given request instance.
     * 
     * @param ServerRequestInterface $request A server request.
     * @param RouteInterface $route A route.
     * @return ServerRequestInterface The server request with the saved route parameters.
     */
    private function saveRouteParametersToRequest(
        ServerRequestInterface $request,
        RouteInterface $route
    ): ServerRequestInterface {
        $routeParameters = $route->getParameters();

        foreach ($routeParameters as $parameterName => $parameterValue) {
            $request = $request->withAttribute($parameterName, $parameterValue);
        }

        return $request;
    }

}
