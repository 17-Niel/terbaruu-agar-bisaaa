<?php

namespace Tests\Feature\Controllers\LowonganPekerjaan;

use App\Http\Controllers\App\LowonganPekerjaan\LowonganPekerjaanController;
use App\Models\LowonganPekerjaanModel;
use App\Models\PerusahaanModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LowonganPekerjaanControllerTest extends TestCase
{
    protected $lowonganMock;

    protected $perusahaanMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Inertia::always
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });

        // Mock Models dengan alias agar kita bisa memanipulasi method static
        $this->lowonganMock = Mockery::mock('alias:'.LowonganPekerjaanModel::class);
        $this->perusahaanMock = Mockery::mock('alias:'.PerusahaanModel::class);

        // --- MOCK STATIC METHOD getJenisOptions ---
        // Ini penting karena method asli hilang saat menggunakan 'alias:'
        $this->lowonganMock->shouldReceive('getJenisOptions')
            ->zeroOrMoreTimes()
            ->andReturn(['Full Time', 'Part Time', 'Contract']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_render_default()
    {
        // Setup Auth
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan', 'GET');
        $request->attributes->set('auth', $auth);

        // Expectation: Dropdown lists
        $this->perusahaanMock->shouldReceive('all')->once()->andReturn(['p1']);

        // Expectation: Query Lowongan
        $this->lowonganMock->shouldReceive('query')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->lowonganMock);

        // when(search) -> tidak dijalankan/null
        $this->lowonganMock->shouldReceive('when')->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('orderByDesc')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('paginate')
            ->with(5)
            ->andReturn('paginateData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/lowongan-pekerjaan/lowongan-pekerjaan-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new LowonganPekerjaanController;
        $res = $controller->index($request);

        // Verifikasi hasil
        $this->assertSame($mockResp, $res);
        $this->assertEquals('paginateData', $captured['lowonganList']());
        $this->assertEquals(['p1'], $captured['perusahaanList']);
        // Verifikasi array statis yang kita mock
        $this->assertEquals(['Full Time', 'Part Time', 'Contract'], $captured['jenisLowonganList']);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_with_search()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan', 'GET', ['search' => 'backend']);
        $request->attributes->set('auth', $auth);

        $this->perusahaanMock->shouldReceive('all')->once()->andReturn([]);

        $this->lowonganMock->shouldReceive('query')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->lowonganMock);

        // intercept when(search, callback)
        $this->lowonganMock->shouldReceive('when')
            ->once()
            ->with('backend', Mockery::type('callable'))
            ->andReturnUsing(function ($search, $callback) {
                $callback($this->lowonganMock, $search);

                return $this->lowonganMock;
            });

        // intercept where(fn($q) => ...) inside the when
        $this->lowonganMock->shouldReceive('where')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($cb) {
                $cb($this->lowonganMock);

                return $this->lowonganMock;
            });

        $this->lowonganMock->shouldReceive('whereRaw')->once()->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('orWhereRaw')->times(2)->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('orderByDesc')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('paginate')->with(5)->andReturn('searchData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/lowongan-pekerjaan/lowongan-pekerjaan-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new LowonganPekerjaanController;
        $controller->index($request);

        $this->assertEquals('searchData', $captured['lowonganList']());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_per_page_invalid_defaults_to_5()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan', 'GET', ['perPage' => 0]);
        $request->attributes->set('auth', $auth);

        $this->perusahaanMock->shouldReceive('all')->once()->andReturn([]);

        $this->lowonganMock->shouldReceive('query')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('where')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('when')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('orderByDesc')->andReturn($this->lowonganMock);
        $this->lowonganMock->shouldReceive('paginate')
            ->with(5)
            ->andReturn('defaultData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/lowongan-pekerjaan/lowongan-pekerjaan-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new LowonganPekerjaanController;
        $controller->index($request);

        $this->assertEquals('defaultData', $captured['lowonganList']());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_fails_without_access()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/lowongan/change', 'POST');
        $request->attributes->set('auth', $auth);

        (new LowonganPekerjaanController)->postChange($request);

        $this->assertEquals(
            'Anda tidak memiliki izin untuk mengolah lowongan pekerjaan.',
            session('error')
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_creates_new_lowongan()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'id_perusahaan' => '1',
            'jenis_lowongan' => 'Full Time',
            'nama_lowongan' => 'Frontend Dev',
        ]);

        $this->lowonganMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($data) => $data['nama_lowongan'] === 'Frontend Dev' &&
                $data['id_admin_pembuat'] === 1
            ))
            ->andReturnTrue();

        (new LowonganPekerjaanController)->postChange($request);

        // PERBAIKAN: Huruf 'P' besar pada 'Pekerjaan' sesuai Controller
        $this->assertEquals(
            'Lowongan Pekerjaan berhasil ditambahkan.',
            session('success')
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_update_fails_when_not_found()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'id_lowongan' => '999',
            'id_perusahaan' => '1',
            'jenis_lowongan' => 'Full Time',
            'nama_lowongan' => 'Error Job',
        ]);

        $this->lowonganMock->shouldReceive('where')
            ->with('id_lowongan', '999')
            ->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('first')->andReturnNull();

        (new LowonganPekerjaanController)->postChange($request);

        $this->assertEquals(
            'Lowongan tidak ditemukan atau Anda tidak memiliki akses.',
            session('error')
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_updates_existing_lowongan()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'id_lowongan' => '10',
            'id_perusahaan' => '1',
            'jenis_lowongan' => 'Part Time',
            'nama_lowongan' => 'Updated Job',
            'kualifikasi_pendidikan' => ['S1'],
        ]);

        $existing = Mockery::mock();
        $existing->shouldIgnoreMissing();
        $existing->shouldReceive('save')->once()->andReturnTrue();

        $this->lowonganMock->shouldReceive('where')
            ->with('id_lowongan', '10')
            ->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('first')->andReturn($existing);

        (new LowonganPekerjaanController)->postChange($request);

        // PERBAIKAN: Huruf 'P' besar pada 'Pekerjaan' sesuai Controller
        $this->assertEquals(
            'Lowongan Pekerjaan berhasil diperbarui.',
            session('success')
        );
        $this->assertEquals('Updated Job', $existing->nama_lowongan);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_fails_without_access()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/lowongan/delete', 'POST');
        $request->attributes->set('auth', $auth);

        (new LowonganPekerjaanController)->postDelete($request);

        $this->assertEquals(
            'Anda tidak memiliki izin untuk mengolah lowongan pekerjaan.',
            session('error')
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_success()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Lowongan Pekerjaan']];
        $request = Request::create('/lowongan/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'ids_lowongan' => ['1', '2'],
        ]);

        $this->lowonganMock->shouldReceive('whereIn')
            ->with('id_lowongan', ['1', '2'])
            ->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->lowonganMock);

        $this->lowonganMock->shouldReceive('delete')->once()->andReturnTrue();

        (new LowonganPekerjaanController)->postDelete($request);

        $this->assertEquals(
            'Lowongan pekerjaan yang dipilih berhasil dihapus.',
            session('success')
        );
    }
}
