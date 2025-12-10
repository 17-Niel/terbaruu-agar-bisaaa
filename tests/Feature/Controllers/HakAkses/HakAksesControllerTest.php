<?php

namespace Tests\Feature\Controllers\HakAkses;

use App\Helper\ToolsHelper;
use App\Http\Api\UserApi;
use App\Http\Controllers\App\HakAkses\HakAksesController;
use App\Models\HakAksesModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HakAksesControllerTest extends TestCase
{
    // Attribut untuk menyimpan mock objek
    protected $hakAksesModelMock;

    protected $userApiMock;

    // Setup sebelum setiap test => dipanggil sebelum test dijalankan
    protected function setUp(): void
    {
        parent::setUp();

        // Clear mock sebelumnya
        Mockery::close();

        // Mock Inertia::always untuk mengembalikan nilai yang diinginkan
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });

        // Buat mock dengan alias
        $this->hakAksesModelMock = Mockery::mock('alias:'.HakAksesModel::class);
        $this->userApiMock = Mockery::mock('alias:'.UserApi::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function index_redirect_ke_home_jika_bukan_editor()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('home'), $response->getTargetUrl());
    }

    #[Test]
    public function index_menampilkan_halaman_jika_editor()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        // Setup mock behavior untuk model dan API
        $this
            ->hakAksesModelMock
            ->shouldReceive('all')
            ->andReturn(collect([(object) ['user_id' => 'user1', 'akses' => 'Admin']]));

        $this
            ->userApiMock
            ->shouldReceive('postReqUsersByIds')
            ->andReturn((object) [
                'data' => (object) [
                    'users' => [(object) ['id' => 'user1', 'name' => 'Test User']],
                ],
            ]);

        ToolsHelper::setAuthToken('fake-token');

        // Mock hanya method render() dari Inertia, biarkan always() berjalan normal
        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->once()
            ->with('app/hak-akses/hak-akses-page', Mockery::any())
            ->andReturn($mockResponse);

        // Biarkan always() dipanggil tanpa mock (gunakan real method)
        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function post_change_berhasil_memperbarui_hak_akses()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses/change', 'POST', [
            'userId' => 'user123',
            'hakAkses' => ['read', 'write'],
        ]);
        $request->attributes->set('auth', $auth);

        // Setup mock query builder untuk delete
        $queryMock = Mockery::mock('stdClass');
        $queryMock->shouldReceive('delete')->once();

        // Setup mock behavior
        $this
            ->hakAksesModelMock
            ->shouldReceive('where')
            ->with('user_id', 'user123')
            ->once()
            ->andReturn($queryMock);

        // Perbaikan: Gunakan Mockery::on() untuk validasi parameter
        $this
            ->hakAksesModelMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['id']) &&
                    is_string($data['id']) &&
                    $data['user_id'] === 'user123' &&
                    $data['akses'] === 'read,write';
            }));

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postChange($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Hak akses berhasil diperbarui.', $response->getSession()->get('success'));
    }

    #[Test]
    public function akses_list_closure_mengembalikan_data_dengan_format_yang_benar()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        // Mock data HakAksesModel
        $mockHakAksesData = collect([
            (object) [
                'id' => '1',
                'user_id' => 'user1',
                'akses' => 'read,write',
            ],
            (object) [
                'id' => '2',
                'user_id' => 'user2',
                'akses' => 'read,delete',
            ],
        ]);

        // Mock data UserApi response
        $mockUsersResponse = (object) [
            'data' => (object) [
                'users' => [
                    (object) ['id' => 'user1', 'name' => 'John Doe', 'email' => 'john@example.com'],
                    (object) ['id' => 'user2', 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
                ],
            ],
        ];

        // Setup mock behavior
        $this
            ->hakAksesModelMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($mockHakAksesData);

        $this
            ->userApiMock
            ->shouldReceive('postReqUsersByIds')
            ->once()
            ->with('fake-token', ['user1', 'user2'])
            ->andReturn($mockUsersResponse);

        ToolsHelper::setAuthToken('fake-token');

        // Capture props yang dikirim ke Inertia::render
        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/hak-akses/hak-akses-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);

        // Panggil closure aksesList dan verifikasi hasilnya
        $aksesListResult = $capturedProps['aksesList']();

        // Verifikasi total data
        $this->assertCount(2, $aksesListResult);
    }

    #[Test]
    public function akses_list_closure_handle_empty_data()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        // Mock empty data
        $this
            ->hakAksesModelMock
            ->shouldReceive('all')
            ->once()
            ->andReturn(collect());

        $this
            ->userApiMock
            ->shouldReceive('postReqUsersByIds')
            ->once()
            ->with('fake-token', [])
            ->andReturn((object) ['data' => (object) ['users' => []]]);

        ToolsHelper::setAuthToken('fake-token');

        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/hak-akses/hak-akses-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);

        $aksesListResult = $capturedProps['aksesList']();
        $this->assertCount(0, $aksesListResult);
    }

    #[Test]
    public function akses_list_closure_handle_user_not_found()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        // Mock HakAkses data dengan user yang tidak ada di response API
        $mockHakAksesData = collect([
            (object) [
                'id' => '1',
                'user_id' => 'user1',
                'akses' => 'read,write',
            ],
        ]);

        // Mock UserApi response tanpa user yang sesuai
        $mockUsersResponse = (object) [
            'data' => (object) [
                'users' => [
                    (object) ['id' => 'user999', 'name' => 'Other User'],  // Different user ID
                ],
            ],
        ];

        $this
            ->hakAksesModelMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($mockHakAksesData);

        $this
            ->userApiMock
            ->shouldReceive('postReqUsersByIds')
            ->once()
            ->with('fake-token', ['user1'])
            ->andReturn($mockUsersResponse);

        ToolsHelper::setAuthToken('fake-token');

        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/hak-akses/hak-akses-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);

        $aksesListResult = $capturedProps['aksesList']();
        $this->assertCount(1, $aksesListResult);

        $item = $aksesListResult[0];
        $this->assertNull($item->user);  // User should be null when not found
        $this->assertEquals(['read', 'write'], $item->data_akses);
    }

    #[Test]
    public function akses_list_closure_handle_api_error()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        $mockHakAksesData = collect([
            (object) [
                'id' => '1',
                'user_id' => 'user1',
                'akses' => 'read',
            ],
        ]);

        // Mock API returning null or error
        $this
            ->hakAksesModelMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($mockHakAksesData);

        $this
            ->userApiMock
            ->shouldReceive('postReqUsersByIds')
            ->once()
            ->with('fake-token', ['user1'])
            ->andReturn(null);  // Simulate API error

        ToolsHelper::setAuthToken('fake-token');

        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/hak-akses/hak-akses-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);

        $aksesListResult = $capturedProps['aksesList']();
        $this->assertCount(1, $aksesListResult);

        $item = $aksesListResult[0];
        $this->assertNull($item->user);  // User should be null when API fails
        $this->assertEquals(['read'], $item->data_akses);
    }

    #[Test]
    public function akses_list_closure_handle_akses_sorting_correctly()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses', 'GET');
        $request->attributes->set('auth', $auth);

        // Mock data dengan akses dalam urutan acak
        $mockHakAksesData = collect([
            (object) [
                'id' => '1',
                'user_id' => 'user1',
                'akses' => 'write,read,delete',
            ],
        ]);

        $mockUsersResponse = (object) [
            'data' => (object) [
                'users' => [
                    (object) ['id' => 'user1', 'name' => 'Test User'],
                ],
            ],
        ];

        $this
            ->hakAksesModelMock
            ->shouldReceive('all')
            ->once()
            ->andReturn($mockHakAksesData);

        $this
            ->userApiMock
            ->shouldReceive('postReqUsersByIds')
            ->once()
            ->with('fake-token', ['user1'])
            ->andReturn($mockUsersResponse);

        ToolsHelper::setAuthToken('fake-token');

        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/hak-akses/hak-akses-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->index($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertSame($mockResponse, $response);

        $aksesListResult = $capturedProps['aksesList']();
        $item = $aksesListResult[0];

        // Verify that akses is sorted alphabetically
        $this->assertEquals(['delete', 'read', 'write'], $item->data_akses);
    }

    #[Test]
    public function post_delete_berhasil_menghapus_hak_akses()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses/delete', 'POST', [
            'userId' => 'user123',
        ]);
        $request->attributes->set('auth', $auth);

        // Setup mock query builder
        $queryMock = Mockery::mock('stdClass');
        $queryMock->shouldReceive('delete')->once();

        // Setup mock behavior
        $this
            ->hakAksesModelMock
            ->shouldReceive('where')
            ->with('user_id', 'user123')
            ->once()
            ->andReturn($queryMock);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDelete($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Hak akses pengguna berhasil dihapus.', $response->getSession()->get('success'));
    }

    #[Test]
    public function post_change_redirect_back_jika_bukan_editor()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];
        $request = Request::create('/hak-akses/change', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postChange($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk mengubah hak akses.', $response->getSession()->get('error'));
    }

    #[Test]
    public function post_delete_redirect_back_jika_bukan_editor()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];  // Bukan Admin
        $request = Request::create('/hak-akses/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDelete($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk mengubah hak akses.', $response->getSession()->get('error'));
    }

    #[Test]
    public function post_delete_selected_redirect_back_jika_bukan_editor()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];  // Bukan Admin
        $request = Request::create('/hak-akses/delete-selected', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDeleteSelected($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk mengubah hak akses.', $response->getSession()->get('error'));
    }

    #[Test]
    public function post_delete_selected_berhasil_menghapus_hak_akses_terpilih()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => ['Admin'], 'roles' => []];
        $request = Request::create('/hak-akses/delete-selected', 'POST', [
            'userIds' => ['user123', 'user456'],
        ]);
        $request->attributes->set('auth', $auth);

        // Setup mock query builder
        $queryMock = Mockery::mock('stdClass');
        $queryMock->shouldReceive('delete')->once();

        // Setup mock behavior
        $this
            ->hakAksesModelMock
            ->shouldReceive('whereIn')
            ->with('user_id', ['user123', 'user456'])
            ->once()
            ->andReturn($queryMock);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDeleteSelected($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Hak akses untuk pengguna yang dipilih berhasil dihapus.', $response->getSession()->get('success'));
    }

    #[Test]
    public function post_delete_selected_berhasil_menghapus_hak_akses_terpilih_dengan_role_admin()
    {
        // =====================================
        // Arrange (Persiapan)
        // =====================================
        $auth = (object) ['akses' => [], 'roles' => ['Admin']];
        $request = Request::create('/hak-akses/delete-selected', 'POST', [
            'userIds' => ['user123', 'user456'],
        ]);
        $request->attributes->set('auth', $auth);

        // Setup mock query builder
        $queryMock = Mockery::mock('stdClass');
        $queryMock->shouldReceive('delete')->once();

        // Setup mock behavior
        $this
            ->hakAksesModelMock
            ->shouldReceive('whereIn')
            ->with('user_id', ['user123', 'user456'])
            ->once()
            ->andReturn($queryMock);

        $controller = new HakAksesController;

        // =====================================
        // Act (Aksi)
        // =====================================
        $response = $controller->postDeleteSelected($request);

        // =====================================
        // Assert (Verifikasi)
        // =====================================
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Hak akses untuk pengguna yang dipilih berhasil dihapus.', $response->getSession()->get('success'));
    }
}
