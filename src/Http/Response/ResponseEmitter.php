<?php
declare(strict_types=1);

namespace LPwork\Http\Response;

use LPwork\Http\Contract\ResponseEmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Default PSR-7 response emitter with streaming support.
 */
final class ResponseEmitter implements ResponseEmitterInterface
{
    /**
     */
    public function emit(ResponseInterface $response, ServerRequestInterface $request): void
    {
        if (!\headers_sent()) {
            \http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    \header($name . ': ' . $value, false);
                }
            }
        }

        if (\strcasecmp($request->getMethod(), 'HEAD') === 0) {
            return;
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read(8192);
            if (\function_exists('flush')) {
                \flush();
            }
        }
    }
}
