<?php

declare(strict_types=1);

namespace TallCms\Cms\Exceptions;

use Exception;

class WebhookDeliveryException extends Exception
{
    /**
     * Create a new webhook delivery exception.
     */
    public function __construct(
        string $message,
        protected ?int $statusCode = null,
        protected ?string $responseBody = null
    ) {
        parent::__construct($message);
    }

    /**
     * Get the HTTP status code from the failed delivery.
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the response body from the failed delivery.
     */
    public function getResponseBody(): ?string
    {
        return $this->responseBody;
    }
}
