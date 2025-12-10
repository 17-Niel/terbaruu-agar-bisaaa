<?php

namespace Tests\Unit\Middleware;

use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Http\Middleware\OptionalAuthMiddleware;
use App\Models\HakAksesModel;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OptionalAuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_lanjut_tanpa_auth_jika_token_kosong()
    {
        // 1. Mock ToolsHelper return token kosong
        $toolsMock = Mockery::mock('alias:'.ToolsHelper::class);
        $toolsMock->shouldReceive('getAuthToken')->once()->andReturn('');

        // 2. Setup Request & Middleware
        $request = Request::create('/any-route', 'GET');
        $middleware = new OptionalAuthMiddleware;

        // 3. Execute
        $response = $middleware->handle($request, function ($req) {
            // Assert di dalam next: 'auth' harus null/tidak ada
            $this->assertNull($req->attributes->get('auth'));

            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_lanjut_tanpa_auth_jika_api_user_gagal()
    {
        // 1. Mock Token ada
        $toolsMock = Mockery::mock('alias:'.ToolsHelper::class);
        $toolsMock->shouldReceive('getAuthToken')->once()->andReturn('valid-token');

        // 2. Mock UserApi return response tanpa data user
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock->shouldReceive('getMe')
            ->with('valid-token')
            ->once()
            ->andReturn((object) ['data' => (object) []]); // Property 'user' tidak ada

        $request = Request::create('/any-route', 'GET');
        $middleware = new OptionalAuthMiddleware;

        $response = $middleware->handle($request, function ($req) {
            // Assert: 'auth' tetap null
            $this->assertNull($req->attributes->get('auth'));

            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_set_auth_dengan_akses_default_kosong()
    {
        // 1. Mock Token
        $toolsMock = Mockery::mock('alias:'.ToolsHelper::class);
        $toolsMock->shouldReceive('getAuthToken')->andReturn('valid-token');

        // 2. Mock UserApi return User Valid
        $userData = (object) ['id' => 123, 'name' => 'John Doe'];
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock->shouldReceive('getMe')
            ->andReturn((object) ['data' => (object) ['user' => $userData]]);

        // 3. Mock HakAksesModel
        // Chain: where(...) -> first()
        $hakAksesMock = Mockery::mock('alias:'.HakAksesModel::class);
        $hakAksesMock->shouldReceive('where')
            ->with('user_id', 123)
            ->andReturn($hakAksesMock); // Return self mock untuk chaining

        // Return object dengan properti akses null
        $hakAksesMock->shouldReceive('first')->andReturn((object) ['akses' => null]);

        $request = Request::create('/any-route', 'GET');
        $middleware = new OptionalAuthMiddleware;

        $middleware->handle($request, function ($req) {
            $auth = $req->attributes->get('auth');

            // Assert: Auth ada
            $this->assertNotNull($auth);
            $this->assertEquals(123, $auth->id);
            // Assert: Akses adalah array kosong (karena null di DB)
            $this->assertIsArray($auth->akses);
            $this->assertEmpty($auth->akses);

            return response('OK');
        });
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function handle_set_auth_dengan_akses_lengkap()
    {
        // 1. Mock Token
        $toolsMock = Mockery::mock('alias:'.ToolsHelper::class);
        $toolsMock->shouldReceive('getAuthToken')->andReturn('valid-token');

        // 2. Mock UserApi return User Valid
        $userData = (object) ['id' => 456, 'name' => 'Admin User'];
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock->shouldReceive('getMe')
            ->andReturn((object) ['data' => (object) ['user' => $userData]]);

        // 3. Mock HakAksesModel
        $hakAksesMock = Mockery::mock('alias:'.HakAksesModel::class);
        $hakAksesMock->shouldReceive('where')
            ->with('user_id', 456)
            ->andReturn($hakAksesMock); // Return self mock untuk chaining

        $hakAksesMock->shouldReceive('first')
            ->andReturn((object) ['akses' => 'Admin,Editor']);

        $request = Request::create('/any-route', 'GET');
        $middleware = new OptionalAuthMiddleware;

        $response = $middleware->handle($request, function ($req) {
            $auth = $req->attributes->get('auth');

            // Assert: Auth set dengan benar
            $this->assertNotNull($auth);
            // Assert: Akses di-explode menjadi array
            $this->assertEquals(['Admin', 'Editor'], $auth->akses);

            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
