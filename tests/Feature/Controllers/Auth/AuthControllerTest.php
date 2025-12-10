<?php

namespace Tests\Feature\Controllers\Auth;

use App\Helper\ApiHelper;
use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mockery::close();

        // Mock Inertia::always untuk mengembalikan nilai yang diinginkan
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function login_menampilkan_halaman_login_dengan_url_sso()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        config(['sdi.sso_authorize_url' => 'https://sso.example.com/auth']);
        config(['sdi.sso_client_id' => 'test-client-id']);

        $expectedUrl = 'https://sso.example.com/auth?client_id=test-client-id';
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('auth/login-page', ['urlLoginSSO' => $expectedUrl])
            ->andReturn($mockResponse);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->login();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function post_login_check_berhasil_dan_redirect_ke_home()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'valid-token-123';

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);

        $userApiMock
            ->shouldReceive('getMe')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);

        $request = new Request(['authToken' => $authToken]);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLoginCheck($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('home'), $response->getTargetUrl());
    }

    #[Test]
    public function post_login_check_gagal_dan_redirect_ke_logout()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'invalid-token';

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'error']);

        $request = new Request(['authToken' => $authToken]);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLoginCheck($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.logout'), $response->getTargetUrl());
    }

    #[Test]
    public function post_login_berhasil_dan_redirect_ke_totp()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('postLogin')
            ->andReturn((object) [
                'data' => (object) ['token' => 'login-token-123'],
            ]);

        $request = new Request([
            'username' => 'testuser',
            'password' => 'password123',
            'systemId' => 'TestSystem',
            'info' => 'TestInfo',
        ]);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLogin($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.totp'), $response->getTargetUrl());
    }

    #[Test]
    public function post_login_redirect_back_dengan_error_jika_token_tidak_ada()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('postLogin')
            ->andReturn((object) [
                'data' => (object) [
                    // Tidak ada property token
                ],
            ]);

        $request = new Request([
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLogin($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());

        $sessionErrors = $response->getSession()->get('errors');
        $this->assertNotNull($sessionErrors);
        $this->assertEquals(
            'Gagal login, silakan coba lagi.',
            $sessionErrors->first('username')
        );
    }

    #[Test]
    public function post_login_redirect_back_dengan_error_jika_response_tidak_valid()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('postLogin')
            ->andReturn(null);  // Response null

        $request = new Request([
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLogin($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotNull($response->getSession()->get('errors'));
    }

    #[Test]
    public function post_login_check_redirect_ke_totp_jika_get_me_gagal()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'valid-token-123';

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);  // Login info berhasil

        $userApiMock
            ->shouldReceive('getMe')
            ->with($authToken)
            ->andReturn((object) ['status' => 'error']);  // GetMe gagal

        $request = new Request(['authToken' => $authToken]);
        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLoginCheck($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.totp'), $response->getTargetUrl());
    }

    #[Test]
    public function post_login_check_redirect_ke_totp_jika_get_me_return_null()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'valid-token-456';

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);  // Login info berhasil

        $userApiMock
            ->shouldReceive('getMe')
            ->with($authToken)
            ->andReturn(null);  // GetMe return null

        $request = new Request(['authToken' => $authToken]);
        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postLoginCheck($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.totp'), $response->getTargetUrl());
    }

    #[Test]
    public function logout_menghapus_token_dan_menampilkan_halaman_logout()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        ToolsHelper::setAuthToken('previous-token');

        // PERBAIKAN: Hapus mock Inertia karena controller redirect ke landing
        // $mockResponse = Mockery::mock(Response::class);
        // Inertia::shouldReceive('render')->once()->with('auth/logout-page')->andReturn($mockResponse);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->logout();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        // PERBAIKAN: Cek redirect ke landing.index
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('landing.index'), $response->getTargetUrl());
        $this->assertEquals('', ToolsHelper::getAuthToken());
    }

    #[Test]
    public function totp_redirect_ke_login_jika_token_tidak_ada()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        ToolsHelper::setAuthToken('');

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->totp();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.login'), $response->getTargetUrl());
    }

    #[Test]
    public function post_totp_berhasil_dan_redirect_ke_home()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'totp-token-123';
        ToolsHelper::setAuthToken($authToken);

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('postTotpVerify')
            ->with($authToken, '123456')
            ->andReturn((object) ['status' => 'success']);

        $request = new Request(['kodeOTP' => '123456']);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postTotp($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        // PERBAIKAN: Diubah ke landing.index sesuai logic di Controller baris 219
        $this->assertEquals(route('landing.index'), $response->getTargetUrl());
    }

    #[Test]
    public function totp_redirect_ke_logout_jika_get_login_info_gagal()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'invalid-token';
        ToolsHelper::setAuthToken($authToken);

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'error']);  // Login info gagal

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->totp();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.logout'), $response->getTargetUrl());
        $this->assertEquals('', ToolsHelper::getAuthToken());  // Token dihapus
    }

    #[Test]
    public function totp_redirect_ke_home_jika_get_me_sukses()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'valid-token';
        ToolsHelper::setAuthToken($authToken);

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);
        $userApiMock
            ->shouldReceive('getMe')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);  // GetMe sukses

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->totp();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('home'), $response->getTargetUrl());
    }

    #[Test]
    public function totp_menampilkan_halaman_dengan_qr_code_jika_get_me_gagal()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'valid-token-totp';
        ToolsHelper::setAuthToken($authToken);

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('getLoginInfo')
            ->with($authToken)
            ->andReturn((object) ['status' => 'success']);
        $userApiMock
            ->shouldReceive('getMe')
            ->with($authToken)
            ->andReturn((object) ['status' => 'error']);  // GetMe gagal
        $userApiMock
            ->shouldReceive('postTotpSetup')
            ->with($authToken)
            ->andReturn((object) [
                'status' => 'success',
                'data' => (object) ['qrCode' => 'qrcode-data'],
            ]);

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->with('auth/totp-page', [
                'authToken' => $authToken,
                'qrCode' => 'qrcode-data',
            ])
            ->andReturn($mockResponse);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->totp();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function post_totp_redirect_ke_login_jika_token_tidak_ada()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        ToolsHelper::setAuthToken('');  // Token kosong

        $request = new Request(['kodeOTP' => '123456']);
        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postTotp($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.login'), $response->getTargetUrl());
    }

    #[Test]
    public function post_totp_redirect_back_dengan_error_jika_verifikasi_gagal()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $authToken = 'valid-token';
        ToolsHelper::setAuthToken($authToken);

        $userApiMock = Mockery::mock('alias:'.UserApi::class);
        $userApiMock
            ->shouldReceive('postTotpVerify')
            ->with($authToken, '123456')
            ->andReturn((object) ['status' => 'error']);  // Verifikasi gagal

        $request = new Request(['kodeOTP' => '123456']);
        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postTotp($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());

        $sessionErrors = $response->getSession()->get('errors');
        $this->assertNotNull($sessionErrors);
        $this->assertEquals(
            'Kode verifikasi tidak valid. Silakan coba lagi.',
            $sessionErrors->first('kodeOTP')
        );
    }

    #[Test]
    public function sso_callback_redirect_ke_login_jika_code_tidak_ada()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $request = new Request;  // Tidak ada parameter code
        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->ssoCallback($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.login'), $response->getTargetUrl());

        $sessionError = $response->getSession()->get('error');
        $this->assertEquals('Kode otorisasi tidak ditemukan', $sessionError);
    }

    #[Test]
    public function sso_callback_redirect_ke_login_jika_access_token_tidak_ada()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        config(['sdi.sso_token_url' => 'https://sso.example.com/token']);
        config(['sdi.sso_client_id' => 'test-client']);
        config(['sdi.sso_client_secret' => 'test-secret']);

        $apiHelperMock = Mockery::mock('alias:'.ApiHelper::class);
        $apiHelperMock
            ->shouldReceive('sendRequest')
            ->with(
                'https://sso.example.com/token',
                'POST',
                [
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'code' => 'auth-code-123',
                ]
            )
            ->andReturn((object) [
                // Tidak ada access_token
            ]);

        $request = new Request(['code' => 'auth-code-123']);
        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->ssoCallback($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('auth.login'), $response->getTargetUrl());

        $sessionError = $response->getSession()->get('error');
        $this->assertEquals('Gagal mendapatkan token akses dari SSO', $sessionError);
    }

    #[Test]
    public function sso_callback_berhasil_dan_redirect_ke_home()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        config(['sdi.sso_token_url' => 'https://sso.example.com/token']);
        config(['sdi.sso_client_id' => 'test-client']);
        config(['sdi.sso_client_secret' => 'test-secret']);

        $apiHelperMock = Mockery::mock('alias:'.ApiHelper::class);
        $apiHelperMock
            ->shouldReceive('sendRequest')
            ->with(
                'https://sso.example.com/token',
                'POST',
                [
                    'client_id' => 'test-client',
                    'client_secret' => 'test-secret',
                    'code' => 'auth-code-123',
                ]
            )
            ->andReturn((object) ['access_token' => 'sso-token-123']);

        $request = new Request(['code' => 'auth-code-123']);

        $controller = new AuthController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->ssoCallback($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('home'), $response->getTargetUrl());
        $this->assertEquals('sso-token-123', ToolsHelper::getAuthToken());
    }
}
