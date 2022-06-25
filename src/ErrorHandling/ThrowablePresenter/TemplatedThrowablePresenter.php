<?php

namespace PajuranCodes\Framework\ErrorHandling\ThrowablePresenter;

use Psr\Http\Message\{
    ResponseInterface,
    ResponseFactoryInterface,
};
use PajuranCodes\{
    Template\Renderer\TemplateRendererInterface,
    Framework\ErrorHandling\ThrowablePresenter\ThrowablePresenter,
};
use Fig\Http\Message\StatusCodeInterface as StatusCode;

/**
 * @author pajurancodes
 */
class TemplatedThrowablePresenter extends ThrowablePresenter {

    /**
     * 
     * @param TemplateRendererInterface $templateRenderer A template renderer.
     * @param string $templateName A template name.
     * @param (string|int|float|bool|null|object|array)[] $context (optional) A list of parameters 
     * to pass to the template.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        bool $debugEnabled,
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly string $templateName,
        private readonly array $context = []
    ) {
        parent::__construct($responseFactory, $debugEnabled);
    }

    /**
     * Present a throwable.
     * 
     * This method returns an "Internal Server 
     * Error" response with the status code 500.
     * 
     * If application's debug mode is not enabled, the throwable is 
     * logged. Further, independent of the application's debug mode 
     * value, a template file is rendered, with the informations 
     * about the throwable and the debug mode value passed for 
     * presentation. The rendered content is then written to 
     * the response body.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.1 6.6.1. 500 Internal Server Error
     */
    public function present(\Throwable $throwable): ResponseInterface {
        $response = $this->responseFactory->createResponse(
            StatusCode::STATUS_INTERNAL_SERVER_ERROR
        );

        if (!$this->debugEnabled) {
            $this->logThrowableToSystemLogger($throwable);
        }

        $context = array_merge($this->context, [
            'debugEnabled' => $this->debugEnabled,
            'throwable' => $throwable,
        ]);

        $bodyContent = $this->templateRenderer->render($this->templateName, $context);

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
