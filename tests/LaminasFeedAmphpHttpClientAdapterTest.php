<?php

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
    }

    public function testCanAcceptClient(): void
    {
        $testBody = "passed" . uniqid();
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
     * @param string|null $responseBody
     * @param array<non-empty-string, list<string>> $responseHeaders
     * @return HttpClient
     */
    private function getHttpClient(?string $responseBody = null, array $responseHeaders = []): HttpClient
    {
        return (new HttpClientBuilder())
            ->intercept($this->getInterceptor($responseBody, $responseHeaders))
            ->build();
    }

    /**
     * @param string|null $body
     * @param array<non-empty-string, list<string>> $headers
     * @return ApplicationInterceptor
     */
    private function getInterceptor(?string $body, array $headers = []): ApplicationInterceptor
    {
        return new class($body, $headers) implements ApplicationInterceptor
        {
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