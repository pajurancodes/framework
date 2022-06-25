<?php

namespace PajuranCodes\Framework\Routing\ThrowablePresenter\RouteNotFound;

use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PajuranCodes\Framework\Routing\ThrowablePresenter\TemplatedThrowablePresenter as BaseTemplatedThrowablePresenter;

/**
 * A throwable presenter.
 * 
 * This component is used to present an exception 
 * indicating that no route was found to the user.
 * 
 * @see \PajuranCodes\Router\Exception\RouteNotFound
 * @author pajurancodes
 */
class TemplatedThrowablePresenter extends BaseTemplatedThrowablePresenter {

    /**
     * Present a throwable.
     * 
     * This method returns a "Not Found" 
     * response with the status code 404.
     * 
     * The throwable informations are presented 
     * in a template file whose rendered content 
     * is written to the response body.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.4 6.5.4. 404 Not Found
     */
    public function present(\Throwable $throwable): ResponseInterface {
        $response = $this->responseFactory->createResponse(
            StatusCode::STATUS_NOT_FOUND
        );

        $context = array_merge($this->context, [
            'message' => $throwable->getMessage(),
            'httpMethod' => $throwable->getHttpMethod(),
            'uriPath' => $throwable->getUriPath(),
        ]);

        $bodyContent = $this->templateRenderer->render($this->templateName, $context);

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
