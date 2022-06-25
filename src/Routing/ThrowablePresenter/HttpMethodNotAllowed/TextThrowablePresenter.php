<?php

namespace PajuranCodes\Framework\Routing\ThrowablePresenter\HttpMethodNotAllowed;

use function implode;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PajuranCodes\Framework\Routing\ThrowablePresenter\ThrowablePresenter;

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
class TextThrowablePresenter extends ThrowablePresenter {

    /**
     * Present a throwable.
     * 
     * This method returns a "Method Not Allowed" 
     * response with the status code 405.
     * 
     * The throwable informations are written 
     * in plain text to the response body.
     * 
     * The HTTP specification requires that a "405 Method Not Allowed" 
     * response include the "Allow:" header to detail available methods 
     * for the requested resource.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.5 6.5.5. 405 Method Not Allowed
     * @link https://tools.ietf.org/html/rfc7231#section-7.4.1 7.4.1. Allow
     */
    public function present(\Throwable $throwable): ResponseInterface {
        $response = $this->responseFactory->createResponse(
            StatusCode::STATUS_METHOD_NOT_ALLOWED
        );

        $allowedMethods = implode(', ', $throwable->getAllowedMethods());

        $response = $response->withHeader('Allow', $allowedMethods);

        $bodyContent = $throwable->getMessage();

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
