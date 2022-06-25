<?php

namespace PajuranCodes\Framework\Routing\RequestHandler\ResourceNotFound;

use function sprintf;
use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
};
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PajuranCodes\Framework\Routing\RequestHandler\ResourceNotFound\ResourceNotFound;

/**
 * @author pajurancodes
 */
class TextResourceNotFound extends ResourceNotFound {

    /**
     * Handles a request and produces a response.
     * 
     * This method returns a "Not Found" 
     * response with the status code 404.
     * 
     * The response body consists 
     * of a plain text message.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.5.4 6.5.4. 404 Not Found
     * 
     * @param ServerRequestInterface $request A server request.
     * @return ResponseInterface The response to the current request.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface {
        $response = $this->responseFactory->createResponse(
            StatusCode::STATUS_NOT_FOUND
        );

        $bodyContent = sprintf(
            'The requested resource could not be found at '
            . 'location "%s", using HTTP method "%s".',
            (string) $request->getUri(),
            $request->getMethod()
        );

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
