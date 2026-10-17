<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core;

use App\Core\Container;
use App\Core\HttpStatus;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Tests\Support\SpyController;
use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
#[UsesClass(Container::class)]
#[UsesClass(Request::class)]
#[UsesClass(Response::class)]
#[UsesClass(HttpStatus::class)]
final class RouterTest extends TestCase
{
    private SpyController $controller;
    private Router $router;

    protected function setUp(): void
    {
        $this->controller = new SpyController();

        $container = new Container();
        $container->set(SpyController::class, fn (): SpyController => $this->controller);

        $this->router = new Router($container);
    }

    public function testDispatchCallsMatchingControllerAction(): void
    {
        $this->router->get('/api/health', [SpyController::class, 'index']);
        $request = new Request('GET', '/api/health', [], []);

        $response = $this->router->dispatch($request);

        self::assertSame('{"action":"index"}', $response->getBody());
        self::assertCount(1, $this->controller->calls);
        self::assertSame('index', $this->controller->calls[0]['action']);
        self::assertSame($request, $this->controller->calls[0]['request'], 'The request must be passed through');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function methodProvider(): array
    {
        return [
            'GET' => ['get'],
            'POST' => ['post'],
            'PUT' => ['put'],
            'DELETE' => ['delete'],
        ];
    }

    #[DataProvider('methodProvider')]
    public function testEachRegisterMethodMatchesItsHttpMethod(string $registerMethod): void
    {
        $this->router->$registerMethod('/api/transactions', [SpyController::class, 'index']);

        $response = $this->router->dispatch(new Request(strtoupper($registerMethod), '/api/transactions', [], []));

        self::assertSame(HttpStatus::Ok, $response->getStatus());
        self::assertCount(1, $this->controller->calls);
    }

    public function testPlaceholderIsPassedAsNamedStringArgument(): void
    {
        $this->router->get('/api/transactions/{id}', [SpyController::class, 'show']);

        $this->router->dispatch(new Request('GET', '/api/transactions/42', [], []));

        self::assertSame('show', $this->controller->calls[0]['action']);
        self::assertSame(['id' => '42'], $this->controller->calls[0]['params']);
    }

    /**
     * Arguments are passed by name, not by position: order in the URL does not matter.
     */
    public function testMultiplePlaceholdersArePassedByName(): void
    {
        $this->router->get('/api/categories/{categoryId}/transactions/{id}', [SpyController::class, 'showNested']);

        $this->router->dispatch(new Request('GET', '/api/categories/3/transactions/42', [], []));

        self::assertSame(['categoryId' => '3', 'id' => '42'], $this->controller->calls[0]['params']);
    }

    /**
     * The placeholder accepts any characters except '/'. Validating it (e.g. digits only) is the controller's job.
     */
    public function testPlaceholderAcceptsNonNumericValue(): void
    {
        $this->router->get('/api/transactions/{id}', [SpyController::class, 'show']);

        $this->router->dispatch(new Request('GET', '/api/transactions/abc', [], []));

        self::assertSame(['id' => 'abc'], $this->controller->calls[0]['params']);
    }

    public function testPlaceholderDoesNotMatchSlash(): void
    {
        $this->router->get('/api/transactions/{id}', [SpyController::class, 'show']);

        $response = $this->router->dispatch(new Request('GET', '/api/transactions/42/extra', [], []));

        self::assertSame(HttpStatus::NotFound, $response->getStatus());
        self::assertSame([], $this->controller->calls);
    }

    public function testTrailingSlashInRouteDefinitionIsIgnored(): void
    {
        $this->router->get('/api/health/', [SpyController::class, 'index']);

        $response = $this->router->dispatch(new Request('GET', '/api/health', [], []));

        self::assertSame(HttpStatus::Ok, $response->getStatus());
    }

    public function testRootRouteStaysRoot(): void
    {
        $this->router->get('/', [SpyController::class, 'index']);

        $response = $this->router->dispatch(new Request('GET', '/', [], []));

        self::assertSame(HttpStatus::Ok, $response->getStatus());
    }

    public function testFirstMatchingRouteWins(): void
    {
        $this->router->get('/api/transactions/{id}', [SpyController::class, 'show']);
        $this->router->get('/api/transactions/latest', [SpyController::class, 'index']);

        $this->router->dispatch(new Request('GET', '/api/transactions/latest', [], []));

        self::assertSame('show', $this->controller->calls[0]['action']);
        self::assertSame(['id' => 'latest'], $this->controller->calls[0]['params']);
    }

    public function testUnknownPathReturns404(): void
    {
        $this->router->get('/api/health', [SpyController::class, 'index']);

        $response = $this->router->dispatch(new Request('GET', '/api/unknown', [], []));

        self::assertSame(HttpStatus::NotFound, $response->getStatus());
        self::assertSame('{"error":"Not Found"}', $response->getBody());
        self::assertSame([], $this->controller->calls);
    }

    public function testRouterWithoutRoutesReturns404(): void
    {
        $response = $this->router->dispatch(new Request('GET', '/', [], []));

        self::assertSame(HttpStatus::NotFound, $response->getStatus());
    }

    public function testKnownPathWithWrongMethodReturns405WithAllowHeader(): void
    {
        $this->router->get('/api/transactions', [SpyController::class, 'index']);
        $this->router->post('/api/transactions', [SpyController::class, 'index']);

        $response = $this->router->dispatch(new Request('DELETE', '/api/transactions', [], []));

        self::assertSame(HttpStatus::MethodNotAllowed, $response->getStatus());
        self::assertSame('{"error":"Method Not Allowed"}', $response->getBody());
        self::assertSame('GET, POST', $response->getHeaders()['Allow']);
        self::assertSame([], $this->controller->calls);
    }

    /**
     * Two GET routes can match the same path ('{id}' and 'latest'); 'GET' must appear only once.
     */
    public function testAllowHeaderContainsNoDuplicates(): void
    {
        $this->router->get('/api/transactions/{id}', [SpyController::class, 'show']);
        $this->router->get('/api/transactions/latest', [SpyController::class, 'index']);

        $response = $this->router->dispatch(new Request('DELETE', '/api/transactions/latest', [], []));

        self::assertSame('GET', $response->getHeaders()['Allow']);
    }

    /**
     * Documents a pitfall: the placeholder name must match the controller parameter name.
     * '{transactionId}' is passed as named argument 'transactionId', but show() expects '$id'.
     */
    public function testPlaceholderNameMustMatchParameterName(): void
    {
        $this->router->get('/api/transactions/{transactionId}', [SpyController::class, 'show']);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Unknown named parameter $transactionId');

        $this->router->dispatch(new Request('GET', '/api/transactions/42', [], []));
    }
}
