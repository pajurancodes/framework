<?php

namespace PajuranCodes\Framework\ErrorHandling\ThrowablePresenter;

use function var_dump;
use function ob_start;
use function ob_end_clean;
use function ob_get_contents;
use Psr\Http\Message\ResponseInterface;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use PajuranCodes\Framework\ErrorHandling\ThrowablePresenter\ThrowablePresenter;

/**
 * @author pajurancodes
 */
class TextThrowablePresenter extends ThrowablePresenter {

    /**
     * Present a throwable.
     * 
     * This method returns an "Internal Server 
     * Error" response with the status code 500.
     * 
     * If application's debug mode is enabled, the informations 
     * about the throwable are dumped. Otherwise the throwable 
     * is logged and a user-friendly message is written in 
     * plain text to the response body.
     * 
     * @link https://tools.ietf.org/html/rfc7231#section-6.6.1 6.6.1. 500 Internal Server Error
     */
    public function present(\Throwable $throwable): ResponseInterface {
        $response = $this->responseFactory->createResponse(
            StatusCode::STATUS_INTERNAL_SERVER_ERROR
        );

        if ($this->debugEnabled) {
            $bodyContent = 'An error occurred during your request.\n\n'
                . $this->dumpThrowableInfos($throwable);
        } else {
            $this->logThrowableToSystemLogger($throwable);
            $bodyContent = 'An error occurred during your request. Please try again later.';
        }

        $response->getBody()->write($bodyContent);

        return $response;
    }

    /**
     * Dump all informations about a throwable.
     * 
     * @param \Throwable $throwable A throwable.
     * @return string The dumped throwable informations.
     */
    private function dumpThrowableInfos(\Throwable $throwable): string {
        ob_start();

        var_dump((string) $throwable);

        $infos = ob_get_contents();

        ob_end_clean();

        return $infos;
    }

}
