<?php

namespace Tests\Unit\Middleware;

use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Http\Middleware\CheckAuthMiddleware;
use App\Models\HakAksesModel;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckAuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Pastikan Mockery bersih sebelum setiap test
        Mockery::close();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function redirect_ke_login_jika_token_tidak_ada()
    {
        ToolsHelper::setAuthToken('');

        $request = Request::create('/app/profile', 'GET');
        $middleware = new CheckAuthMiddleware;

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());

        // PERBAIKAN: Ubah ekspektasi ke auth.login sesuai logika middleware
        $this->assertEquals(route('auth.login'), $response->getTargetUrl());
    }

    #[Test]
    public function redirect_ke_login_jika_token_invalid()
    {
        ToolsHelper::setAuthToken('invalid-token');

        // Mock UserApi mengembalikan response tanpa user data
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getMe')
            ->with('invalid-token')
            ->andReturn((object) [
                'data' => (object) [
                    // Tidak ada property 'user'
                ],
            ]);

        $request = Request::create('/app/profile', 'GET');
        $middleware = new CheckAuthMiddleware;

        $response = $middleware->handle($request, function () {});

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('auth/login', $response->getTargetUrl());
    }

    #[Test]
    public function redirect_ke_landing_jika_bukan_admin()
    {
        // KASUS: User Login, tapi TIDAK punya akses 'Admin'

        $userData = (object) [
            'id' => 'user-biasa-id',
            'name' => 'User Biasa',
        ];

        ToolsHelper::setAuthToken('valid-token-user');

        // Mock UserApi
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getMe')
            ->with('valid-token-user')
            ->andReturn((object) [
                'data' => (object) [
                    'user' => $userData,
                ],
            ]);

        // Mock HakAksesModel (Hanya punya akses view, edit TAPI TIDAK ADA Admin)
        $hakAksesMock = Mockery::mock('alias:'.HakAksesModel::class);
        $hakAksesMock
            ->shouldReceive('where')
            ->with('user_id', $userData->id)
            ->once()
            ->andReturnSelf();
        $hakAksesMock
            ->shouldReceive('first')
            ->once()
            ->andReturn((object) ['akses' => 'view,edit']);

        $request = Request::create('/app/profile', 'GET');
        $middleware = new CheckAuthMiddleware;

        $response = $middleware->handle($request, function () {});

        // ASSERT: Harus redirect (302) ke landing page
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect());

        // PERBAIKAN: Gunakan route() helper agar akurat, meskipun URLnya root (localhost:8000)
        $this->assertEquals(route('landing.index'), $response->getTargetUrl());
    }

    #[Test]
    public function melanjutkan_request_jika_user_adalah_admin()
    {
        // KASUS: Happy Path (User Login DAN punya akses 'Admin')

        $userData = (object) [
            'id' => '8357fda6-67f7-4a99-8f01-9847d6920599',
            'name' => 'Admin User',
        ];

        ToolsHelper::setAuthToken('valid-token-admin');

        // Mock UserApi
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getMe')
            ->with('valid-token-admin')
            ->andReturn((object) [
                'data' => (object) [
                    'user' => $userData,
                ],
            ]);

        // Mock HakAksesModel (Punya akses Admin)
        $hakAksesMock = Mockery::mock('alias:'.HakAksesModel::class);
        $hakAksesMock
            ->shouldReceive('where')
            ->with('user_id', $userData->id)
            ->once()
            ->andReturnSelf();
        $hakAksesMock
            ->shouldReceive('first')
            ->once()
            ->andReturn((object) ['akses' => 'view,edit,Admin']);

        $request = Request::create('/app/profile', 'GET');
        $middleware = new CheckAuthMiddleware;

        $response = $middleware->handle($request, function ($req) {
            // Assertion di dalam controller/next
            $auth = $req->attributes->get('auth');

            // Pastikan array akses ter-explode dengan benar dan mengandung Admin
            $this->assertContains('Admin', $auth->akses);
            $this->assertContains('view', $auth->akses);

            return response('Success', 200);
        });

        // ASSERT: Status harus 200 OK (Bukan redirect)
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }
}
