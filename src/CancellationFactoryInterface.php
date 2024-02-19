<?php

namespace thgs\Adapter\LaminasFeedHttpClient;

use Amp\Cancellation;

interface CancellationFactoryInterface
{
    public function createCancellation(string $uri, array $headers = []): Cancellation;
}