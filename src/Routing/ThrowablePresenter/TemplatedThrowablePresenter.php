<?php

namespace PajuranCodes\Framework\Routing\ThrowablePresenter;

use PajuranCodes\{
    Template\Renderer\TemplateRendererInterface,
    Framework\Routing\ThrowablePresenter\ThrowablePresenter,
};
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @author pajurancodes
 */
abstract class TemplatedThrowablePresenter extends ThrowablePresenter {

    /**
     * 
     * @param TemplateRendererInterface $templateRenderer A template renderer.
     * @param string $templateName A template name.
     * @param (string|int|float|bool|null|object|array)[] $context (optional) A list of parameters 
     * to pass to the template.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        protected readonly TemplateRendererInterface $templateRenderer,
        protected readonly string $templateName,
        protected readonly array $context = []
    ) {
        parent::__construct($responseFactory);
    }

}
