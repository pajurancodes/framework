<?php

namespace PajuranCodes\Framework\ErrorHandling\ThrowablePresenter\Whoops;

use Psr\Http\Message\{
    ResponseInterface,
    ResponseFactoryInterface,
};
use PajuranCodes\{
    Template\Renderer\TemplateRendererInterface,
    Framework\ErrorHandling\ThrowablePresenter\Whoops\ThrowablePresenter,
};
use Whoops\RunInterface as WhoopsRunnerInterface;
use Fig\Http\Message\StatusCodeInterface as StatusCode;

/**
 * @author pajurancodes
 */
class TemplatedThrowablePresenter extends ThrowablePresenter {

    /**
     * 
     * @param TemplateRendererInterface $templateRenderer A template renderer.
     * @param string $templateName A template name.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        bool $debugEnabled,
        WhoopsRunnerInterface $whoopsRunner,
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly string $templateName
    ) {
        parent::__construct($responseFactory, $debugEnabled, $whoopsRunner);
    }

    /**
     * Present a throwable.
     * 
     * This method returns an "Internal Server 
     * Error" response with the status code 500.
     * 
     * If application's  debug mode is enabled, the throwable is presented 
     * using the Whoops library. Otherwise the throwable is logged and a 
     * template file (containing a user-friendly message, for example) is 
     * rendered and written to the response body.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.1 6.6.1. 500 Internal Server Error
     */
    public function present(\Throwable $throwable): ResponseInterface {
        $response = $this->responseFactory->createResponse(
            StatusCode::STATUS_INTERNAL_SERVER_ERROR
        );

        if ($this->debugEnabled) {
            $bodyContent = $this->whoopsRunner->handleException($throwable);
        } else {
            $this->logThrowableToSystemLogger($throwable);
            $bodyContent = $this->templateRenderer->render($this->templateName);
        }

        $response->getBody()->write($bodyContent);

        return $response;
    }

}
