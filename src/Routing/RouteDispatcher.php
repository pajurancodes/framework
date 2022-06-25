<?php

namespace PajuranCodes\Framework\Routing;

use PajuranCodes\Router\{
    Route,
    RouteInterface,
    Exception\RouteNotFound,
};
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
use Psr\Http\Server\{
    MiddlewareInterface,
    RequestHandlerInterface,
};
use PajuranCodes\Framework\Routing\{
    RouteInvokerInterface,
    ThrowablePresenter\ThrowablePresenterInterface,
};
use Psr\Container\ContainerInterface;

/**
 * A route dispatching middleware.
 * 
 * This component assumes that a matched route was already 
 * found and saved by previously processed middlewares.
 * 
 * @author pajurancodes
 */
class RouteDispatcher implements MiddlewareInterface {

    /**
     *
     * @param RouteInvokerInterface $routeInvoker A route invoker.
     * @param ThrowablePresenterInterface $throwablePresenter A throwable presenter.
     * @param ContainerInterface|null $container (optional) A dependency injection container.
     * @param string $requestAttributeForSavingMatchedRoute (optional) The name of a request 
     * attribute under which a matched route was saved into the request.
     */
    public function __construct(
        private readonly RouteInvokerInterface $routeInvoker,
        private readonly ThrowablePresenterInterface $throwablePresenter,
        private readonly ?ContainerInterface $container = null,
        private readonly string $requestAttributeForSavingMatchedRoute = Route::class
    ) {
        
    }

    /**
     * Process an incoming server request.
     * 
     * This method grabs a route which was already saved 
     * by previously processed middlewares, registers the 
     * server request instance to the DI container and 
     * invokes the route.
     * 
     * @param ServerRequestInterface $request A server request.
     * @param RequestHandlerInterface $handler A request handler.
     * @return ResponseInterface The response to the current request.
     * @throws RouteNotFound No route was already saved by previously processed middlewares.
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $route = $this->getSavedRouteFromRequest($request);

        if (!isset($route) || !($route instanceof RouteInterface)) {
            try {
                throw new RouteNotFound(
                        $request->getMethod(),
                        $request->getUri()->getPath()
                );
            } catch (RouteNotFound $exception) {
                return $this->throwablePresenter->present($exception);
            }
        }

        $this->assignRequestToContainer($request);

        return $this->invokeRoute($route);
    }

    /**
     * Get a previously saved route from the given request.
     * 
     * @param ServerRequestInterface $request A server request.
     * @return RouteInterface|null The found route or null.
     */
    private function getSavedRouteFromRequest(ServerRequestInterface $request): ?RouteInterface {
        return $request->getAttribute(
                $this->requestAttributeForSavingMatchedRoute,
                null
        );
    }

    /**
     * Assign a request object to the container.
     * 
     * The assignation MUST take place after the 
     * complete customization of the request instance.
     * 
     * @param ServerRequestInterface $request A server request.
     * @return static
     * 
     * @todo Since not all containers implement 'Psr\Container\ContainerInterface::set', how to avoid using it here?
     */
    private function assignRequestToContainer(ServerRequestInterface $request): static {
        if (isset($this->container)) {
            $this->container->set(ServerRequestInterface::class, $request);
        }

        return $this;
    }

    /**
     * Invoke a route.
     * 
     * @param RouteInterface $route A route.
     * @return ResponseInterface The response to the current request.
     */
    private function invokeRoute(RouteInterface $route): ResponseInterface {
        return $this->routeInvoker->invoke($route);
    }

}
