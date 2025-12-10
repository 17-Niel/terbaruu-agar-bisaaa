<?php

namespace Tests\Feature\Controllers\Perusahaan;

use App\Http\Controllers\App\Perusahaan\PerusahaanController;
use App\Models\PerusahaanModel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerusahaanControllerTest extends TestCase
{
    // Mock Objects
    protected $perusahaanModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Mock Inertia::always
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });

        // 2. Mock PerusahaanModel sebagai Alias (Penyebab utama konflik)
        $this->perusahaanModelMock = Mockery::mock('alias:'.PerusahaanModel::class);

        // 3. Fake Storage
        Storage::fake('public');

        // 4. Mock DB Facade untuk transaction di postDelete
        DB::shouldReceive('transaction')
            ->byDefault()
            ->andReturnUsing(function ($callback) {
                $callback(); // Eksekusi closure transaction
            });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // TEST METHOD: index()
    // =========================================================================

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_berhasil_render_halaman_perusahaan()
    {
        // Arrange
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];
        $request = Request::create('/perusahaan', 'GET');
        $request->attributes->set('auth', $auth);

        $this->perusahaanModelMock->shouldReceive('query')->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('when')
            ->with('', Mockery::type('callable'))
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('orderByDesc')
            ->with('created_at')
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('paginate')
            ->with(5)
            ->andReturn('paginatedResult');

        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/perusahaan/perusahaan-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new PerusahaanController;

        // Act
        $response = $controller->index($request);

        // Assert
        $this->assertSame($mockResponse, $response);
        $this->assertArrayHasKey('perusahaanList', $capturedProps);
        $this->assertEquals('paginatedResult', $capturedProps['perusahaanList']());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_berhasil_render_dengan_pencarian()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan', 'GET', ['search' => 'Teknologi']);
        $request->attributes->set('auth', $auth);

        $this->perusahaanModelMock->shouldReceive('query')
            ->once()
            ->andReturn($this->perusahaanModelMock);

        // 1) Intersep 'when' dan jalankan callback-nya
        $this->perusahaanModelMock
            ->shouldReceive('when')
            ->once()
            ->with('Teknologi', Mockery::type('callable'))
            ->andReturnUsing(function ($search, $callback) {
                // Callback when di controller menerima ($query)
                $callback($this->perusahaanModelMock);

                return $this->perusahaanModelMock;
            });

        // 2) Intersep 'where' (nested closure), jalankan callbacknya
        $this->perusahaanModelMock
            ->shouldReceive('where')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($callback) {
                // Callback where menerima ($q)
                $callback($this->perusahaanModelMock);

                return $this->perusahaanModelMock;
            });

        // 3) Mock whereRaw & orWhereRaw → pastikan dipanggil
        $this->perusahaanModelMock->shouldReceive('whereRaw')->once()->andReturn($this->perusahaanModelMock);
        $this->perusahaanModelMock->shouldReceive('orWhereRaw')->once()->andReturn($this->perusahaanModelMock);

        // Mock sisa chain
        $this->perusahaanModelMock->shouldReceive('orderByDesc')->once()->andReturn($this->perusahaanModelMock);
        $this->perusahaanModelMock->shouldReceive('paginate')->once()->with(5)->andReturn('searchResult');

        // Capture props agar bisa memanggil lazy prop
        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->once()
            ->with('app/perusahaan/perusahaan-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new PerusahaanController;

        // Act
        $response = $controller->index($request);

        // Assert
        $this->assertSame($mockResponse, $response);
        $this->assertArrayHasKey('perusahaanList', $capturedProps);
        // Panggil lazy prop
        $this->assertSame('searchResult', $capturedProps['perusahaanList']());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_menggunakan_default_per_page_jika_input_invalid()
    {
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];
        $request = Request::create('/perusahaan', 'GET', ['perPage' => 0]);
        $request->attributes->set('auth', $auth);

        $this->perusahaanModelMock->shouldReceive('query')->andReturn($this->perusahaanModelMock);
        $this->perusahaanModelMock->shouldReceive('when')->andReturn($this->perusahaanModelMock);
        $this->perusahaanModelMock->shouldReceive('orderByDesc')->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('paginate')
            ->with(5)
            ->andReturn('paginatedResult');

        $capturedProps = [];
        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')
            ->once()
            ->with('app/perusahaan/perusahaan-page', Mockery::capture($capturedProps))
            ->andReturn($mockResponse);

        $controller = new PerusahaanController;
        $controller->index($request);

        // panggil lazy prop agar paginate(5) benar-benar terjadi pada jalur perPage <= 0
        $this->assertEquals('paginatedResult', $capturedProps['perusahaanList']());

        $this->addToAssertionCount(1);
    }

    // =========================================================================
    // TEST METHOD: postChange()
    // =========================================================================

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_gagal_jika_tidak_punya_akses_editor()
    {
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new PerusahaanController;
        $response = $controller->postChange($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk mengolah data perusahaan.', $response->getSession()->get('error'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_berhasil_create_data_baru()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $file = UploadedFile::fake()->image('logo.jpg');

        $request->merge([
            'nama' => 'PT Testing Indonesia',
            'lokasi' => 'Jakarta',
            'website' => 'https://test.com',
            'industri' => 'IT',
            'deskripsi' => 'Perusahaan testing.',
        ]);
        $request->files->set('url_logo', $file);

        $this->perusahaanModelMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['nama'] === 'PT Testing Indonesia' &&
                       str_contains($data['url_logo'], 'uploads/logos');
            }))
            ->andReturn(true);

        $controller = new PerusahaanController;
        $response = $controller->postChange($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Perusahaan Baru berhasil ditambahkan.', $response->getSession()->get('success'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_berhasil_create_data_baru_tanpa_logo()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/change', 'POST');
        $request->attributes->set('auth', $auth);

        // Tidak mengirim file -> hasFile('url_logo') === false
        $request->merge([
            'nama' => 'PT Tanpa Logo',
            'lokasi' => 'Jakarta',
            'website' => 'https://nologo.test',
            'industri' => 'IT',
            'deskripsi' => 'Perusahaan tanpa logo.',
        ]);

        $this->perusahaanModelMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['nama'] === 'PT Tanpa Logo'
                    && array_key_exists('url_logo', $data)
                    && $data['url_logo'] === null
                    && ! empty($data['id_perusahaan']);
            }))
            ->andReturnTrue();

        $controller = new PerusahaanController;
        $response = $controller->postChange($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Perusahaan Baru berhasil ditambahkan.', $response->getSession()->get('success'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_berhasil_update_data_existing()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $idPerusahaan = '12345';
        $request->merge([
            'id_perusahaan' => $idPerusahaan,
            'nama' => 'PT Update',
            'lokasi' => 'Bandung',
            'website' => 'https://update.com',
            'industri' => 'Retail',
            'deskripsi' => 'Deskripsi baru',
        ]);

        $existingPerusahaan = Mockery::mock();
        $existingPerusahaan->shouldIgnoreMissing();
        $existingPerusahaan->shouldReceive('save')->once()->andReturnTrue();

        $this->perusahaanModelMock
            ->shouldReceive('where')
            ->with('id_perusahaan', $idPerusahaan)
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('first')
            ->andReturn($existingPerusahaan);

        $controller = new PerusahaanController;
        $response = $controller->postChange($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Data Perusahaan berhasil diperbarui.', $response->getSession()->get('success'));
        $this->assertEquals('PT Update', $existingPerusahaan->nama);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_berhasil_update_data_dengan_upload_logo_baru()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $idPerusahaan = '12345';
        $file = UploadedFile::fake()->image('logo-baru.jpg');

        $request->merge([
            'id_perusahaan' => $idPerusahaan,
            'nama' => 'PT Logo Baru',
            'lokasi' => 'Medan',
            'website' => 'https://logobaru.com',
            'industri' => 'F&B',
            'deskripsi' => 'Deskripsi',
        ]);
        $request->files->set('url_logo', $file);

        $existingPerusahaan = Mockery::mock();
        $existingPerusahaan->shouldIgnoreMissing();
        $existingPerusahaan->shouldReceive('save')->once()->andReturnTrue();

        $this->perusahaanModelMock
            ->shouldReceive('where')
            ->with('id_perusahaan', $idPerusahaan)
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('first')
            ->andReturn($existingPerusahaan);

        $controller = new PerusahaanController;
        $response = $controller->postChange($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Data Perusahaan berhasil diperbarui.', $response->getSession()->get('success'));

        $this->assertStringContainsString('/storage/uploads/logos', $existingPerusahaan->url_logo);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_gagal_update_jika_id_tidak_ditemukan()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'id_perusahaan' => 'id_palsu',
            'nama' => 'Valid Name',
            'lokasi' => 'Valid',
            'website' => 'Valid',
            'industri' => 'Valid',
            'deskripsi' => 'Valid',
        ]);

        $this->perusahaanModelMock
            ->shouldReceive('where')
            ->with('id_perusahaan', 'id_palsu')
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('first')
            ->andReturnNull();

        $controller = new PerusahaanController;
        $response = $controller->postChange($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Data Perusahaan tidak ditemukan.', $response->getSession()->get('error'));
    }

    // =========================================================================
    // TEST METHOD: postDelete() (DIPERBAIKI UNTUK MENGHINDARI REDECLARE MOCK)
    // =========================================================================

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_gagal_tanpa_akses()
    {
        $auth = (object) ['akses' => ['User'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $controller = new PerusahaanController;
        $response = $controller->postDelete($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda tidak memiliki izin untuk menghapus data.', $response->getSession()->get('error'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_berhasil_menghapus_data_tanpa_logo()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $idsToDelete = ['id_1', 'id_2'];
        $request->merge(['ids' => $idsToDelete]);

        // PERBAIKAN: Gunakan Mockery::mock() generik dan shouldIgnoreMissing()
        // agar tidak berkonflik dengan alias mock PerusahaanModel di setUp().
        $perusahaan1 = Mockery::mock()->shouldIgnoreMissing();
        $perusahaan1->shouldReceive('delete')->once()->andReturn(true);
        $perusahaan1->url_logo = null; // Tidak punya logo

        $perusahaan2 = Mockery::mock()->shouldIgnoreMissing();
        $perusahaan2->shouldReceive('delete')->once()->andReturn(true);
        $perusahaan2->url_logo = null; // Tidak punya logo

        $mockCollection = collect([$perusahaan1, $perusahaan2]);

        $this->perusahaanModelMock
            ->shouldReceive('whereIn')
            ->with('id_perusahaan', $idsToDelete)
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $controller = new PerusahaanController;
        $response = $controller->postDelete($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Data Perusahaan yang dipilih berhasil dihapus.', $response->getSession()->get('success'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_berhasil_menghapus_data_dengan_logo()
    {
        $auth = (object) ['akses' => ['Perusahaan'], 'roles' => ['User']];
        $request = Request::create('/perusahaan/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $idsToDelete = ['id_logo'];
        $request->merge(['ids' => $idsToDelete]);

        $urlLogo = '/storage/uploads/logos/test-logo.jpg';
        $pathLogo = 'uploads/logos/test-logo.jpg';

        // Simulasikan file ada di storage
        Storage::disk('public')->put($pathLogo, 'dummy content');
        $this->assertTrue(Storage::disk('public')->exists($pathLogo)); // Sanity check

        // PERBAIKAN: Gunakan Mockery::mock() generik dan shouldIgnoreMissing()
        $perusahaanWithLogo = Mockery::mock()->shouldIgnoreMissing();
        $perusahaanWithLogo->shouldReceive('delete')->once()->andReturn(true);
        $perusahaanWithLogo->url_logo = $urlLogo;

        $mockCollection = collect([$perusahaanWithLogo]);

        $this->perusahaanModelMock
            ->shouldReceive('whereIn')
            ->with('id_perusahaan', $idsToDelete)
            ->andReturn($this->perusahaanModelMock);

        $this->perusahaanModelMock
            ->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);

        $controller = new PerusahaanController;
        $response = $controller->postDelete($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Data Perusahaan yang dipilih berhasil dihapus.', $response->getSession()->get('success'));

        // Pastikan file logo dihapus
        $this->assertFalse(Storage::disk('public')->exists($pathLogo));
    }
}
