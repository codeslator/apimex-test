<?php

namespace App\Renderer;

use Psr\Http\Message\ResponseInterface;

/**
 * A JSON response renderer.
 */
final class JsonRenderer
{
    /**
     * Write JSON to the response body.
     *
     * This method prepares the response object to return an HTTP JSON
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param mixed $data The data
     * @param int $options Json encoding options
     *
     * @return ResponseInterface The response
     */
    public function json(
        ResponseInterface $response,
        mixed $data = null,
        int $options = JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
    ): ResponseInterface {
        $response = $response->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, $options));

        return $response;
    }

    /**
     * Write JSON to the response body.
     *
     * This method prepares the response object to return an HTTP JSON
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param mixed $data The data
     * @param int $code code success
     * @param int $options Json encoding options
     *
     * @return ResponseInterface The response
     */
    public function response(
        ResponseInterface $response,
        mixed $data = null,
        int $code = 200,
        int $options = JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
    ): ResponseInterface {

        $values = [
            'code' => $code,
            'status' => 'success',
            'data' => $data
        ];

        $response = $response->withHeader('Content-Type', 'application/json')->withStatus($code);
        $response->getBody()->write((string)json_encode($values, $options));

        return $response;
    }

    /**
     * Write JSON to the response body.
     *
     * This method prepares the response object to return an HTTP JSON
     * response to the client.
     *
     * @param ResponseInterface $response The response
     * @param mixed $message The data
     * @param int $code code error
     * @param int $options Json encoding options
     *
     * @return ResponseInterface The response
     */
    public function error(
        ResponseInterface $response,
        mixed $message,
        int $code = 400,
        int $options = JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
    ): ResponseInterface {

        $values = [
            'code' => $code,
            'status' => 'fail',
            'message' => $message
        ];

        $response = $response->withHeader('Content-Type', 'application/json')->withStatus($code);
        $response->getBody()->write((string)json_encode($values, $options));

        return $response;
    }
}
