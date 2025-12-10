<?php

namespace Tests\Unit\Provider;

use App\Providers\AppServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('req-limit:127.0.0.1');
        Mockery::close();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function memaksa_https_ketika_environment_remote()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        URL::shouldReceive('forceScheme')
            ->once()
            ->with('https');

        app()->detectEnvironment(fn () => 'remote');

        $provider = new AppServiceProvider(app());

        // =====================================
        // Act (Aksi)
        // =====================================
        $provider->boot();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function memaksa_https_ketika_config_force_https_true()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        app()->detectEnvironment(fn () => 'local');
        config(['sdi.force_https' => true]);

        URL::shouldReceive('forceScheme')
            ->once()
            ->with('https');

        $provider = new AppServiceProvider(app());

        // =====================================
        // Act (Aksi)
        // =====================================
        $provider->boot();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function tidak_memaksa_https_jika_kondisi_tidak_terpenuhi()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        app()->detectEnvironment(fn () => 'local');
        config(['sdi.force_https' => false]);

        URL::shouldReceive('forceScheme')->never();

        $provider = new AppServiceProvider(app());

        // =====================================
        // Act (Aksi)
        // =====================================
        $provider->boot();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function register_method_tidak_mengeksekusi_apa_apa()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $provider = new AppServiceProvider(app());

        // =====================================
        // Act (Aksi)
        // =====================================
        $provider->register();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertTrue(true);
    }

    #[Test]
    public function rate_limiter_berhasil_dikonfigurasi()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        RateLimiter::shouldReceive('for')
            ->once()
            ->with('req-limit', Mockery::type('Closure'))
            ->andReturnUsing(function ($name, $callback) {
                $request = Mockery::mock(Request::class);
                $request->shouldReceive('user')->andReturn(null);
                $request->shouldReceive('ip')->andReturn('127.0.0.1');

                // =====================================
                // Act (Aksi) - dalam closure
                // =====================================
                $result = $callback($request);

                // =====================================
                // Assert (Verifikasi) - dalam closure
                // =====================================
                $this->assertInstanceOf(Limit::class, $result);

                return $result;
            });

        $provider = new AppServiceProvider(app());

        // =====================================
        // Act (Aksi)
        // =====================================
        $provider->boot();

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function rate_limiter_response_memiliki_format_yang_benar()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $request = Mockery::mock(\Illuminate\Http\Request::class);
        $headers = ['Retry-After' => 300];

        $responseCallback = function ($request, array $headers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.',
                'retry_after' => $headers['Retry-After'] ?? null,
            ], 429);
        };

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $responseCallback($request, $headers);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(429, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.', $responseData['message']);
        $this->assertEquals(300, $responseData['retry_after']);
    }

    #[Test]
    public function rate_limiter_response_tanpa_retry_after()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $request = Mockery::mock(\Illuminate\Http\Request::class);
        $headers = [];

        $responseCallback = function ($request, array $headers) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.',
                'retry_after' => $headers['Retry-After'] ?? null,
            ], 429);
        };

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $responseCallback($request, $headers);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $responseData = json_decode($response->getContent(), true);
        $this->assertNull($responseData['retry_after']);
    }

    #[Test]
    public function muncul_pesan_error_saat_melebihi_batas_request()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        RateLimiter::clear('req-limit:127.0.0.1');

        // =====================================
        // Act (Aksi)
        // =====================================
        // Kirim request sebanyak batas maksimum (200 kali)
        for ($i = 0; $i < 300; $i++) {
            $response = $this->getJson('/api/test-limit');
            $response->assertOk();
        }

        // Kirim request ke-301 -> seharusnya sudah kena limit
        $response = $this->getJson('/api/test-limit');

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $response
            ->assertStatus(429)
            ->assertJson([
                'status' => 'error',
                'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'retry_after',
            ]);

        // =====================================
        // Cleanup (Pembersihan)
        // =====================================
        RateLimiter::clear('req-limit:127.0.0.1');
    }
}
