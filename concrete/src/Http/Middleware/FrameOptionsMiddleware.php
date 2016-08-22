<?php

namespace Concrete\Core\Http\Middleware;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Http\Request;
use Concrete\Core\Utility\Service\Validation\Strings;

/**
 * Middleware for applying frame options
 * @package Concrete\Core\Http\Middleware
 */
class FrameOptionsMiddleware implements MiddlewareInterface
{

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    private $config;

    /**
     * @var \Concrete\Core\Utility\Service\Validation\Strings
     */
    private $stringValidator;

    public function __construct(Repository $config, Strings $stringValidator)
    {
        $this->config = $config;
        $this->stringValidator = $stringValidator;
    }

    /**
     * @param \Concrete\Core\Http\Request $request
     * @param \Concrete\Core\Http\Middleware\FrameInterface $frame
     * @return Response
     */
    public function process(Request $request, FrameInterface $frame)
    {
        $response = $frame->next($request);

        if ($response->headers->has('X-Frame-Options') === false) {
            $x_frame_options = $this->config->get('concrete.security.misc.x_frame_options');
            if ($this->stringValidator->notempty($x_frame_options)) {
                $response->headers->set('X-Frame-Options', $x_frame_options);
            }
        }

        return $response;
    }

}
