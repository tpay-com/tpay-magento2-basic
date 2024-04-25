<?php

declare(strict_types=1);

class OpenApiException extends \Exception
{
    public function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function channelAndGroupCollision(): OpenApiException
    {
        return new OpenApiException('Channel and Group should not be declared at the same time', 400);
    }
}
