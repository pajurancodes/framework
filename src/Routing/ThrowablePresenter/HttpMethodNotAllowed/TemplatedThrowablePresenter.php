<?php

namespace PajuranCodes\Framework\Routing\ThrowablePresenter\HttpMethodNotAllowed;

use function implode;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PajuranCodes\Framework\Routing\ThrowablePresenter\TemplatedThrowablePresenter as BaseTemplatedThrowablePresenter;

/**
 * A throwable presenter.
 * 
 * This component is used to present an exception 
 * indicating that the HTTP method of the request 
 * is not supported to the user.
 * 
 * @see \PajuranCodes\Router\Exception\HttpMethodNotAllowed
 * @author pajurancodes
 */
class TemplatedThrowablePresenter extends BaseTemplatedThrowablePresenter {

    /**
     * Present a throwable.
     * 
     * This method returns a "Method Not Allowed" 
     * response with the status code 405.
     * 
     * The throwable informations are presented 
     * in a template file whose rendered content 
     * is written to the response body.
     * 
     * The HTTP specification requires that a "405 Method Not Allowed" 
     * response include the "Allow:" header to detail available methods 
     * for the requested resource.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.5 6.5.5. 405 Method Not Allowed
     * @link https://tools.ietf.org/html/rfc7231#section-7.4.1 7.4.1. Allow
     */
    public function present(\Throwable $throwable): ResponseInterface {
        $allowedMethods = implode(', ', $throwable->getAllowedMethods());

        $response = $this->responseFactory
            ->createResponse(StatusCode::STATUS_METHOD_NOT_ALLOWED)
            ->withHeader('Allow', $allowedMethods)
        ;

        $context = array_merge($this->context, [
            'message' => $throwable->getMessage(),
            'httpMethod' => $throwable->getHttpMethod(),
            'allowedMethods' => $allowedMethods,
        ]);

        $bodyContent = $this->templateRenderer->render($this->templateName, $context);

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
