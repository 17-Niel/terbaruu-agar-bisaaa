<?php

// -----------------------------------------------------------------------------
// 1. HEADER MOCKING
// -----------------------------------------------------------------------------

namespace App\Http\Controllers\App\CampusHiring;

function header($string, $replace = true, $http_response_code = null)
{
    // Mock header agar tidak error di PHPUnit
}

// -----------------------------------------------------------------------------
// 2. TEST CLASS
// -----------------------------------------------------------------------------

namespace Tests\Feature\Controllers\CampusHiring;

use App\Http\Controllers\App\CampusHiring\CampusHiringController;
use App\Models\CampusHiringModel;
use App\Models\LamaranCampusHiringModel;
use App\Models\PerusahaanModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampusHiringControllerTest extends TestCase
{
    protected $campusHiringMock;
    protected $perusahaanMock;
    protected $lamaranMock;

    protected function setUp(): void
    {
        parent::setUp();

        // MOCK INERTIA ALWAYS
        Inertia::shouldReceive('always')
            ->zeroOrMoreTimes()
            ->andReturnUsing(function ($value) {
                $mock = Mockery::mock('Inertia\AlwaysProp');
                $mock->shouldIgnoreMissing();
                $mock->capturedValue = $value;
                return $mock;
            });

        // MOCK MODELS
        $this->campusHiringMock = Mockery::mock('alias:' . CampusHiringModel::class);
        $this->perusahaanMock   = Mockery::mock('alias:' . PerusahaanModel::class);
        $this->lamaranMock      = Mockery::mock('alias:' . LamaranCampusHiringModel::class);

        $this->campusHiringMock->shouldReceive('getJenisOptions')
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
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring', 'GET');
        $request->attributes->set('auth', $auth);

        $this->perusahaanMock->shouldReceive('all')->once()->andReturn(['p1']);

        $this->campusHiringMock->shouldReceive('query')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->campusHiringMock);

        $this->campusHiringMock->shouldReceive('when')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('orderByDesc')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('paginate')
            ->with(5)
            ->andReturn('paginateData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/campus-hiring/campus-hiring-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new CampusHiringController;
        $res        = $controller->index($request);

        $this->assertSame($mockResp, $res);
        $this->assertEquals('paginateData', $captured['campusHiringList']());
        $this->assertEquals(1, $captured['page']->capturedValue);
        $this->assertEquals(5, $captured['perPage']->capturedValue);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_with_search()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring', 'GET', ['search' => 'java']);
        $request->attributes->set('auth', $auth);

        $this->perusahaanMock->shouldReceive('all')->once()->andReturn([]);

        $this->campusHiringMock->shouldReceive('query')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('where')
            ->with('id_admin_pembuat', 1)
            ->andReturn($this->campusHiringMock);

        // Closure coverage 'when'
        $this->campusHiringMock->shouldReceive('when')
            ->once()
            ->with('java', Mockery::type('callable'))
            ->andReturnUsing(function ($search, $callback) {
                $callback($this->campusHiringMock, $search);
                return $this->campusHiringMock;
            });

        // Closure coverage 'where' (nested for search)
        $this->campusHiringMock->shouldReceive('where')
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($cb) {
                $cb($this->campusHiringMock);
                return $this->campusHiringMock;
            });

        $this->campusHiringMock->shouldReceive('whereRaw')->once()->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('orWhereRaw')->times(2)->andReturn($this->campusHiringMock);
        
        $this->campusHiringMock->shouldReceive('orderByDesc')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('paginate')->with(5)->andReturn('searchData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/campus-hiring/campus-hiring-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new CampusHiringController;
        $controller->index($request);

        $this->assertEquals('searchData', $captured['campusHiringList']());
        $this->assertEquals('java', $captured['search']->capturedValue);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_per_page_invalid_defaults_to_5()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring', 'GET', ['perPage' => 0]);
        $request->attributes->set('auth', $auth);

        $this->perusahaanMock->shouldReceive('all')->once()->andReturn([]);
        $this->campusHiringMock->shouldReceive('query')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('where')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('when')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('orderByDesc')->andReturn($this->campusHiringMock);

        $this->campusHiringMock->shouldReceive('paginate')->with(5)->andReturn('defaultData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/campus-hiring/campus-hiring-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new CampusHiringController;
        $controller->index($request);

        $this->assertEquals('defaultData', $captured['campusHiringList']());
        $this->assertEquals(5, $captured['perPage']->capturedValue);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_fails_without_access()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/app/campus-hiring/change', 'POST');
        $request->attributes->set('auth', $auth);

        (new CampusHiringController)->postChange($request);

        $this->assertEquals('Anda tidak memiliki izin untuk mengolah data Campus Hiring.', session('error'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_creates_new_campus_hiring()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring/change', 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge([
            'id_perusahaan'      => '1',
            'jenis_lowongan'     => 'Full Time',
            'nama_campus_hiring' => 'IT Trainee',
        ]);

        $this->campusHiringMock->shouldReceive('create')->once()->andReturnTrue();

        (new CampusHiringController)->postChange($request);

        $this->assertEquals('Campus Hiring berhasil ditambahkan.', session('success'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_creates_new_campus_hiring_with_empty_id_string()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring/change', 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge([
            'id_campus_hiring'   => '',
            'id_perusahaan'      => '1',
            'jenis_lowongan'     => 'Full Time',
            'nama_campus_hiring' => 'IT Trainee',
        ]);

        $this->campusHiringMock->shouldReceive('create')->once()->andReturnTrue();
        $this->campusHiringMock->shouldNotReceive('where');

        (new CampusHiringController)->postChange($request);

        $this->assertEquals('Campus Hiring berhasil ditambahkan.', session('success'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_update_fails_when_not_found()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring/change', 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge([
            'id_campus_hiring'   => '999',
            'id_perusahaan'      => '1',
            'jenis_lowongan'     => 'Full Time',
            'nama_campus_hiring' => 'Error Job',
        ]);

        $this->campusHiringMock->shouldReceive('where')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('first')->andReturnNull();

        (new CampusHiringController)->postChange($request);

        $this->assertEquals('Data tidak ditemukan atau Anda tidak memiliki akses.', session('error'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_updates_existing_campus_hiring()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring/change', 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge([
            'id_campus_hiring'       => '10',
            'id_perusahaan'          => '1',
            'jenis_lowongan'         => 'Part Time',
            'nama_campus_hiring'     => 'Updated Job',
            'kualifikasi_pendidikan' => ['S1'],
            'departemen'             => 'IT',
        ]);

        $existing = Mockery::mock();
        $existing->shouldIgnoreMissing();
        $existing->shouldReceive('save')->once()->andReturnTrue();

        $this->campusHiringMock->shouldReceive('where')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('first')->andReturn($existing);

        (new CampusHiringController)->postChange($request);

        $this->assertEquals('Campus Hiring berhasil diperbarui.', session('success'));
        $this->assertEquals('Updated Job', $existing->nama_campus_hiring);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_fails_without_access()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/app/campus-hiring/delete', 'POST');
        $request->attributes->set('auth', $auth);

        (new CampusHiringController)->postDelete($request);

        $this->assertEquals('Anda tidak memiliki izin untuk mengolah data Campus Hiring.', session('error'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_fails_with_empty_ids()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring/delete', 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge(['ids_campus_hiring' => []]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        (new CampusHiringController)->postDelete($request);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_success()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Campus Hiring']];
        $request = Request::create('/app/campus-hiring/delete', 'POST');
        $request->attributes->set('auth', $auth);
        $request->merge(['ids_campus_hiring' => ['1', '2']]);

        $this->campusHiringMock->shouldReceive('whereIn')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('where')->andReturn($this->campusHiringMock);
        $this->campusHiringMock->shouldReceive('delete')->once()->andReturnTrue();

        (new CampusHiringController)->postDelete($request);

        $this->assertEquals('Data Campus Hiring yang dipilih berhasil dihapus.', session('success'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function download_applicants_generates_excel_correctly()
    {
        $pelamar1 = (object) [
            'nama_pelamar'    => 'Budi',
            'email'           => 'b@c.com',
            'url_cv'          => 'https://cv.com/budi.pdf',
            'tanggal_lamaran' => '2023-10-01 10:00:00',
        ];

        $this->lamaranMock->shouldReceive('where')
            ->with('id_campus_hiring', 'job-1')
            ->andReturn($this->lamaranMock);
        $this->lamaranMock->shouldReceive('orderBy')->andReturn($this->lamaranMock);
        $this->lamaranMock->shouldReceive('get')->andReturn(collect([$pelamar1]));

        $job = (object) ['nama_campus_hiring' => 'Software Engineer'];
        $this->campusHiringMock->shouldReceive('find')
            ->with('job-1')
            ->andReturn($job);

        // MOCK SPREADSHEET components
        $sheetMock = Mockery::mock();
        $sheetMock->shouldIgnoreMissing();

        $styleMock = Mockery::mock();
        $styleMock->shouldIgnoreMissing();
        $fontMock = Mockery::mock();
        $fontMock->shouldIgnoreMissing();
        $fontMock->shouldReceive('setBold')->andReturnSelf();
        $fontMock->shouldReceive('setSize')->andReturnSelf();
        
        $alignMock = Mockery::mock();
        $alignMock->shouldIgnoreMissing();
        $alignMock->shouldReceive('setHorizontal')->andReturnSelf();

        // HUBUNGKAN RANGKAIAN MOCK
        $styleMock->shouldReceive('getFont')->andReturn($fontMock);
        $styleMock->shouldReceive('getAlignment')->andReturn($alignMock);

        $sheetMock->shouldReceive('getStyle')->andReturn($styleMock);
        $sheetMock->shouldReceive('getColumnDimension')->andReturnSelf();
        $sheetMock->shouldReceive('setAutoSize')->andReturnSelf();

        // Verify Set Data
        $sheetMock->shouldReceive('setCellValue')->with('B4', 'Budi');
        $sheetMock->shouldReceive('setCellValue')->with('D4', '01/10/2023 10:00'); // Check formatted date

        $spreadsheetMock = Mockery::mock('overload:PhpOffice\PhpSpreadsheet\Spreadsheet');
        $spreadsheetMock->shouldReceive('getActiveSheet')->andReturn($sheetMock);
        $spreadsheetMock->shouldIgnoreMissing();

        $writerMock = Mockery::mock('overload:PhpOffice\PhpSpreadsheet\Writer\Xlsx');
        $writerMock->shouldReceive('save')
            ->once()
            ->with('php://output')
            ->andReturnUsing(function () {
                throw new \RuntimeException('Excel Saved Successfully');
            });

        $controller = new CampusHiringController;

        try {
            $controller->downloadApplicants('job-1');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Excel Saved Successfully', $e->getMessage());
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function download_applicants_handles_null_values()
    {
        $pelamarNull = (object) [
            'nama_pelamar'    => 'Unknown',
            'url_cv'          => 'link',
            'tanggal_lamaran' => null, // Ini akan memicu branch 'else' -> '-'
        ];

        $this->lamaranMock->shouldReceive('where')->andReturn($this->lamaranMock);
        $this->lamaranMock->shouldReceive('orderBy')->andReturn($this->lamaranMock);
        $this->lamaranMock->shouldReceive('get')->andReturn(collect([$pelamarNull]));

        // Nama job null akan memicu fallback ke 'Data'
        $jobNullName = (object) ['nama_campus_hiring' => null];
        $this->campusHiringMock->shouldReceive('find')->andReturn($jobNullName);

        // MOCK SPREADSHEET (Full Chain)
        $sheetMock = Mockery::mock();
        $sheetMock->shouldIgnoreMissing();

        $styleMock = Mockery::mock();
        $styleMock->shouldIgnoreMissing();

        $fontMock = Mockery::mock();
        $fontMock->shouldIgnoreMissing();
        $fontMock->shouldReceive('setBold')->andReturnSelf();
        $fontMock->shouldReceive('setSize')->andReturnSelf();

        $alignMock = Mockery::mock();
        $alignMock->shouldIgnoreMissing();
        $alignMock->shouldReceive('setHorizontal')->andReturnSelf();

        // Chain linkage
        $styleMock->shouldReceive('getFont')->andReturn($fontMock);
        $styleMock->shouldReceive('getAlignment')->andReturn($alignMock);

        $sheetMock->shouldReceive('getStyle')->andReturn($styleMock);
        $sheetMock->shouldReceive('getColumnDimension')->andReturnSelf();
        $sheetMock->shouldReceive('setAutoSize')->andReturnSelf();

        // EXPECTATION:
        // 1. Title harus mengandung 'Data' karena nama job null
        $sheetMock->shouldReceive('setCellValue')->with('A1', 'Daftar Pelamar - Data'); 
        // 2. Kolom D harus berisi '-' karena tanggal null
        $sheetMock->shouldReceive('setCellValue')->with('D4', '-');

        $spreadsheetMock = Mockery::mock('overload:PhpOffice\PhpSpreadsheet\Spreadsheet');
        $spreadsheetMock->shouldReceive('getActiveSheet')->andReturn($sheetMock);
        $spreadsheetMock->shouldIgnoreMissing();

        $writerMock = Mockery::mock('overload:PhpOffice\PhpSpreadsheet\Writer\Xlsx');
        $writerMock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function () {
                throw new \RuntimeException('Done');
            });

        $controller = new CampusHiringController;

        try {
            $controller->downloadApplicants('job-null');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Done', $e->getMessage());
        }
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function download_applicants_handles_not_found_job()
    {
        // CampusHiring tidak ditemukan
        $this->campusHiringMock->shouldReceive('find')
            ->with('job-missing')
            ->andReturnNull();

        $controller = new CampusHiringController;
        $response   = $controller->downloadApplicants('job-missing');

        $this->assertEquals('Lowongan tidak ditemukan.', session('error'));
        $this->assertNotNull($response);
    }
}
