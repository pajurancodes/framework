<?php

namespace PajuranCodes\Framework\ErrorHandling\ThrowablePresenter\Whoops;

use Psr\Http\Message\ResponseFactoryInterface;
use Whoops\RunInterface as WhoopsRunnerInterface;
use PajuranCodes\Framework\ErrorHandling\ThrowablePresenter\ThrowablePresenter as BaseThrowablePresenter;

/**
 * @author pajurancodes
 */
abstract class ThrowablePresenter extends BaseThrowablePresenter {

    /**
     * 
     * @param WhoopsRunnerInterface $whoopsRunner A Whoops runner.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        bool $debugEnabled,
        protected readonly WhoopsRunnerInterface $whoopsRunner
    ) {
        parent::__construct($responseFactory, $debugEnabled);

        $this->customizeWhoopsRunner();
    }

    /**
     * Customize the Whoops runner.
     * 
     * @return static
     */
    private function customizeWhoopsRunner(): static {
        // Don't allow Whoops to terminate script execution.
        $this->whoopsRunner->allowQuit(false);

        /*
         * Don't allow Whoops to send output produced by handlers 
         * directly to the client. Instead, the handlers' response 
         * will be packaged into a HTTP response abstraction.
         */
        $this->whoopsRunner->writeToOutput(false);

        return $this;
    }

}
