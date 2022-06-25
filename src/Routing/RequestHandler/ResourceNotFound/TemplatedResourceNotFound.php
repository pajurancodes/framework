<?php

namespace PajuranCodes\Framework\Routing\RequestHandler\ResourceNotFound;

use Psr\Http\Message\{
    ResponseInterface,
    ServerRequestInterface,
    ResponseFactoryInterface,
};
use PajuranCodes\{
    Template\Renderer\TemplateRendererInterface,
    Framework\Routing\RequestHandler\ResourceNotFound\ResourceNotFound,
};
use Fig\Http\Message\StatusCodeInterface as StatusCode;

/**
 * @author pajurancodes
 */
class TemplatedResourceNotFound extends ResourceNotFound {

    /**
     * 
     * @param TemplateRendererInterface $templateRenderer A template renderer.
     * @param string $templateName A template name.
     * @param (string|int|float|bool|null|object|array)[] $context (optional) A list of parameters 
     * to pass to the template.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly string $templateName,
        private readonly array $context = []
    ) {
        parent::__construct($responseFactory);
    }

    /**
     * Handles a request and produces a response.
     * 
     * This method returns a "Not Found" 
     * response with the status code 404.
     * 
     * The response body consists 
     * of a rendered template file.
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

        $context = array_merge($this->context, [
            'uriPath' => $request->getUri()->getPath(),
            'httpMethod' => $request->getMethod(),
        ]);

        $bodyContent = $this->templateRenderer->render($this->templateName, $context);

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
