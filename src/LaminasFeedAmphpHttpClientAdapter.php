<?php declare(strict_types=1);

namespace thgs\Adapter\LaminasFeedHttpClient;

use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\HttpMessage;
use Amp\NullCancellation;
use Laminas\Feed\Reader\Http\HeaderAwareClientInterface;
use Laminas\Feed\Reader\Http\Response as LaminasFeedHttpResponse;
use Laminas\Feed\Reader\Reader;

/**
 * @psalm-import-type HeaderMapType from HttpMessage
 */
final class LaminasFeedAmphpHttpClientAdapter implements HeaderAwareClientInterface
{
    private DelegateHttpClient $client;

    public function __construct(
        ?DelegateHttpClient $client = null,
        private ?CancellationFactoryInterface $cancellationFactory = null
    ) {
        $this->client = $client ?? (new HttpClientBuilder())->build();
    }

    public static function installNew(
        ?DelegateHttpClient $client = null,
        ?CancellationFactoryInterface $cancellationFactory = null
    ): self {
        return (new self($client, $cancellationFactory))->install();
    }

    public function getClient(): DelegateHttpClient
    {
        return $this->client;
    }

    /**
     * @param string $uri
     * @param array<non-empty-string, array<string>> $headers
     * @return LaminasFeedHttpResponse
     */
    public function get($uri, array $headers = [])
    {
        $amphpRequest = new Request($uri);
        $amphpRequest->setHeaders($headers);

        $response = $this->client->request(
            $amphpRequest,
            $this->cancellationFactory?->createCancellation($uri, $headers) ?? new NullCancellation()
        );

        $responseHeaders = [];
        foreach ($response->getHeaders() as $name => $value) {
            $responseHeaders[$name] = \implode(', ', $value);
        }

        return new LaminasFeedHttpResponse(
            $response->getStatus(),
            $response->getBody()->buffer(),
            $responseHeaders
        );
    }

    public function install(): self
    {
        Reader::setHttpClient($this);
        return $this;
    }
}
