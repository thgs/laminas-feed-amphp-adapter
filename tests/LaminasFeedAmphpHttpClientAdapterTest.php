<?php declare(strict_types=1);

namespace thgs\Adapter\LaminasFeedHttpClient\Tests;

use Amp\Cancellation;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Laminas\Feed\Reader\Http\Response as LaminasResponse;
use Laminas\Feed\Reader\Reader;
use PHPUnit\Framework\TestCase;
use thgs\Adapter\LaminasFeedHttpClient\LaminasFeedAmphpHttpClientAdapter;

class LaminasFeedAmphpHttpClientAdapterTest extends TestCase
{
    public function testCanInstallItself(): void
    {
        $adapterInstalled = LaminasFeedAmphpHttpClientAdapter::installNew();

        $this->assertSame(Reader::getHttpClient(), $adapterInstalled);

        $adapter = new LaminasFeedAmphpHttpClientAdapter();
        $adapter->install();

        $this->assertSame(Reader::getHttpClient(), $adapter);
    }

    public function testCanAcceptClient(): void
    {
        $testBody = "passed" . \uniqid();
        $httpClient = $this->getHttpClient($testBody, []);
        $subject = new LaminasFeedAmphpHttpClientAdapter($httpClient);
        $response = $subject->get('http://asdfghjklqyweiuqw');

        $this->assertInstanceOf(LaminasResponse::class, $response);
        $this->assertEquals($response->getBody(), $testBody);
    }

    public function testCanReturnAcceptedClient(): void
    {
        $httpClient = $this->getHttpClient();
        $subject = new LaminasFeedAmphpHttpClientAdapter($httpClient);
        $this->assertEquals($httpClient, $subject->getClient());
    }

    public function testCanSetHeaders(): void
    {
        $url = 'http://fjdkls';
        $headers = ['testing' => ['test-string']];

        $fakeClient = new class() implements DelegateHttpClient {
            /** @var string[][] */
            private array $requestHeaders = [];

            public function request(Request $request, Cancellation $cancellation): Response
            {
                $this->requestHeaders = $request->getHeaders();

                return new Response(
                    protocolVersion: '1.1',
                    status: 200,
                    reason: 'reason',
                    headers: $this->requestHeaders,
                    body: null,
                    request: $request
                );
            }

            public function getRequestHeaders(): array
            {
                return $this->requestHeaders;
            }
        };

        $subject = new LaminasFeedAmphpHttpClientAdapter($fakeClient);
        $subject->get($url, $headers);

        $this->assertSame($headers, $fakeClient->getRequestHeaders());
    }

    /**
     * @param non-empty-string $headerName
     * @param list<string> $headerValue
     * @dataProvider provideHeaders
     */
    public function testResponseHeaderLineHasStringValues(
        string $headerName,
        string $retrievalHeaderName,
        array $headerValue,
        ?string $expected
    ): void {
        $httpClient = $this->getHttpClient(responseHeaders: [$headerName => $headerValue]);
        $subject = new LaminasFeedAmphpHttpClientAdapter($httpClient);
        $response = $subject->get('http://fdjkslfjsdkl');

        $headerLine = $response->getHeaderLine($retrievalHeaderName);
        $this->assertEquals($expected, $headerLine);
    }

    public function provideHeaders(): \Generator
    {
        yield 'single value' => [
             'Custom-Header',
             'Custom-Header',
             ['1'],
             '1'
         ];

        yield 'multi value' => [
            'Custom-Header',
            'Custom-Header',
            ['1', '2'],
            '1, 2'
        ];
    }

    public function testResponseHeaderLineCanHandleEmptyHeaders(): void
    {
        $subject = new LaminasFeedAmphpHttpClientAdapter($this->getHttpClient());
        $response = $subject->get('http://fdjkslfjsdkl');

        $headerLine = $response->getHeaderLine('Something-Unknown');
        /** @psalm-suppress DocblockTypeContradiction   Due to $default */
        $this->assertNull($headerLine);
    }

    /**
     * @param array<non-empty-string, list<string>> $responseHeaders
     */
    private function getHttpClient(?string $responseBody = null, array $responseHeaders = []): HttpClient
    {
        return (new HttpClientBuilder())
            ->intercept($this->getInterceptor($responseBody, $responseHeaders))
            ->build();
    }

    /**
     * @param array<non-empty-string, list<string>> $headers
     */
    private function getInterceptor(?string $body, array $headers = []): ApplicationInterceptor
    {
        return new class($body, $headers) implements ApplicationInterceptor {
            public function __construct(
                private ?string $body,
                /** @var array<non-empty-string, list<string>> */
                private array $headers = []
            ) {
            }

            public function request(Request $request, Cancellation $cancellation, DelegateHttpClient $httpClient): Response
            {
                return new Response(
                    protocolVersion: '1.1',
                    status: 200,
                    reason: 'reason',
                    headers: $this->headers,
                    body: $this->body,
                    request: $request
                );
            }
        };
    }
}
