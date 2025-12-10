<?php

namespace Tests\Feature\Controllers\App\Landingpage;

use App\Http\Controllers\App\Landingpage\LandingpageController;
use App\Models\BannerModel;
use App\Models\CampusHiringModel;
use App\Models\LamaranCampusHiringModel;
use App\Models\LowonganPekerjaanModel;
use App\Models\PerusahaanModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\PresenceVerifierInterface;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LandingpageControllerTest extends TestCase
{
    protected $bannerModelMock;

    protected $campusHiringModelMock;

    protected $lamaranCampusHiringModelMock;

    protected $lowonganPekerjaanModelMock;

    protected $perusahaanModelMock;

    protected function setUp(): void
    {
        parent::setUp();
        Mockery::close();
        Carbon::setTestNow(Carbon::parse('2025-01-01'));

        // Mock Validator Database Presence
        $verifier = Mockery::mock(PresenceVerifierInterface::class);
        $verifier->shouldReceive('setConnection')->andReturnSelf();
        $verifier->shouldReceive('getCount')->andReturn(1);
        $this->app['validator']->setPresenceVerifier($verifier);

        // Mock Inertia
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });

        // Mock Models
        $this->bannerModelMock = Mockery::mock('alias:'.BannerModel::class);
        $this->campusHiringModelMock = Mockery::mock('alias:'.CampusHiringModel::class);
        $this->lamaranCampusHiringModelMock = Mockery::mock('alias:'.LamaranCampusHiringModel::class);
        $this->lowonganPekerjaanModelMock = Mockery::mock('alias:'.LowonganPekerjaanModel::class);
        $this->perusahaanModelMock = Mockery::mock('alias:'.PerusahaanModel::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createPaginatorMock()
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $paginator->shouldReceive('withQueryString')->andReturnSelf();

        return $paginator;
    }

    // Helper untuk mengeksekusi closure query builder (untuk coverage baris di dalam closure)
    protected function callbackExecuteQueryClosure()
    {
        return Mockery::on(function ($arg) {
            if ($arg instanceof Closure) {
                $qMock = Mockery::mock('stdClass');
                $qMock->shouldReceive('where')->andReturnSelf();
                $qMock->shouldReceive('orWhere')->andReturnSelf();
                $arg($qMock); // Eksekusi baris kode di dalam closure controller

                return true;
            }

            return false;
        });
    }

    // ==============================================
    // Test untuk method index(Request $request)
    // ==============================================

    #[Test]
    public function index_default_tab_lowongan_renders_correctly()
    {
        $request = Request::create('/', 'GET');

        $this->bannerModelMock->shouldReceive('where')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('get')->andReturn(collect([]));

        $this->lowonganPekerjaanModelMock->shouldReceive('query')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('join')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('select')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('paginate')->with(9)->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->once()->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->index($request);

        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function index_lowongan_tab_with_search_and_degree_logic()
    {
        // TARGET: Cover logika if($search) dan if($degree) di dalam switch case 'lowongan' method index()
        $request = Request::create('/', 'GET', [
            'search' => 'Backend',
            'degree' => 'S1',
        ]);

        $this->bannerModelMock->shouldReceive('where')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('get')->andReturn(collect([]));

        $this->lowonganPekerjaanModelMock->shouldReceive('query')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('join')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('select')->andReturnSelf();

        // 1. Cover Search Closure
        $this->lowonganPekerjaanModelMock->shouldReceive('where')
            ->with($this->callbackExecuteQueryClosure())
            ->andReturnSelf();

        // 2. Cover Degree Filter
        $this->lowonganPekerjaanModelMock->shouldReceive('whereJsonContains')
            ->with('t_lowongan_pekerjaan.kualifikasi_pendidikan', 'S1')
            ->andReturnSelf();

        $this->lowonganPekerjaanModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('paginate')->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->index($request);

        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function index_campus_hiring_tab_with_search_and_degree_filters()
    {
        // TARGET: Cover logika if($search) dan if($degree) di dalam switch case 'campus-hiring' method index()
        $request = Request::create('/', 'GET', [
            'tab' => 'campus-hiring',
            'search' => 'Backend',
            'degree' => 'S1',
        ]);

        $this->bannerModelMock->shouldReceive('where')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('get')->andReturn(collect([]));

        $this->campusHiringModelMock->shouldReceive('query')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('join')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('select')->andReturnSelf();

        // Cover Search Closure
        $this->campusHiringModelMock->shouldReceive('where')
            ->with($this->callbackExecuteQueryClosure())
            ->andReturnSelf();

        // Cover Degree Filter
        $this->campusHiringModelMock->shouldReceive('whereJsonContains')
            ->with('t_campus_hiring.kualifikasi_pendidikan', 'S1')
            ->andReturnSelf();

        $this->campusHiringModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('paginate')->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->index($request);

        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function index_perusahaan_tab_calculates_jobs_count()
    {
        $request = Request::create('/', 'GET', ['tab' => 'perusahaan', 'search' => 'Test']);

        $this->bannerModelMock->shouldReceive('where')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->bannerModelMock->shouldReceive('get')->andReturn(collect([]));

        $this->perusahaanModelMock->shouldReceive('query')->andReturnSelf();
        // Cover search di tab perusahaan (method index)
        $this->perusahaanModelMock->shouldReceive('where')->with('nama', 'LIKE', '%Test%')->andReturnSelf();
        $this->perusahaanModelMock->shouldReceive('orderBy')->andReturnSelf();

        // Mock Transform Logic
        $companyObj = (object) ['id_perusahaan' => 1, 'nama' => 'PT Test'];
        $collectionMock = Mockery::mock(Collection::class);
        $collectionMock->shouldReceive('transform')->andReturnUsing(function ($callback) use ($companyObj, $collectionMock) {
            $callback($companyObj);

            return $collectionMock;
        });

        $paginatorMock = $this->createPaginatorMock();
        $paginatorMock->shouldReceive('getCollection')->andReturn($collectionMock);
        $this->perusahaanModelMock->shouldReceive('paginate')->andReturn($paginatorMock);

        // Mock Counts
        $this->lowonganPekerjaanModelMock->shouldReceive('where')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('count')->andReturn(5);

        $this->campusHiringModelMock->shouldReceive('where')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('count')->andReturn(2);

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->index($request);

        $this->assertSame($mockResponse, $response);
    }

    // ==============================================
    // Test untuk method perusahaan(Request $request)
    // ==============================================

    #[Test]
    public function perusahaan_page_renders_with_search_and_counts()
    {
        $request = Request::create('/perusahaan', 'GET', ['search' => 'Tech']);

        $this->perusahaanModelMock->shouldReceive('query')->andReturnSelf();
        $this->perusahaanModelMock->shouldReceive('where')->with('nama', 'LIKE', '%Tech%')->andReturnSelf();
        $this->perusahaanModelMock->shouldReceive('orderBy')->andReturnSelf();

        $companyObj = (object) ['id_perusahaan' => 99];
        $collectionMock = Mockery::mock(Collection::class);
        $collectionMock->shouldReceive('transform')->andReturnUsing(function ($callback) use ($companyObj, $collectionMock) {
            $callback($companyObj);

            return $collectionMock;
        });

        $paginatorMock = $this->createPaginatorMock();
        $paginatorMock->shouldReceive('getCollection')->andReturn($collectionMock);
        $this->perusahaanModelMock->shouldReceive('paginate')->with(10)->andReturn($paginatorMock);

        $this->lowonganPekerjaanModelMock->shouldReceive('where')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('count')->andReturn(1);

        $this->campusHiringModelMock->shouldReceive('where')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('count')->andReturn(1);

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->once()->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->perusahaan($request);

        $this->assertSame($mockResponse, $response);
    }

    // ==============================================
    // Test untuk method lowongan(Request $request)
    // ==============================================

    #[Test]
    public function lowongan_page_renders_with_auth_data()
    {
        $authObj = (object) ['id' => 1, 'name' => 'John', 'username' => 'john', 'alias' => 'user'];
        $request = Request::create('/lowongan', 'GET');
        $request->attributes->set('auth', $authObj);

        $this->lowonganPekerjaanModelMock->shouldReceive('query')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('join')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('select')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('paginate')->with(10)->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->once()->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->lowongan($request);

        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function lowongan_page_filters_search_and_degree()
    {
        // TARGET: Cover logika if($search) dan if($degree) di method lowongan()
        $request = Request::create('/lowongan', 'GET', ['search' => 'Dev', 'degree' => 'D3']);

        $this->lowonganPekerjaanModelMock->shouldReceive('query')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('join')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('select')->andReturnSelf();

        // 1. Cover Search Closure dengan helper callbackExecuteQueryClosure
        $this->lowonganPekerjaanModelMock->shouldReceive('where')
            ->with($this->callbackExecuteQueryClosure())
            ->andReturnSelf();

        // 2. Cover Degree
        $this->lowonganPekerjaanModelMock->shouldReceive('whereJsonContains')
            ->with('t_lowongan_pekerjaan.kualifikasi_pendidikan', 'D3')
            ->andReturnSelf();

        $this->lowonganPekerjaanModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->lowonganPekerjaanModelMock->shouldReceive('paginate')->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->lowongan($request);

        $this->assertSame($mockResponse, $response);
    }

    // ==============================================
    // Test untuk method campusHiring(Request $request)
    // ==============================================

    #[Test]
    public function campus_hiring_page_renders_correctly()
    {
        $request = Request::create('/campus-hiring', 'GET');

        $this->campusHiringModelMock->shouldReceive('query')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('join')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('select')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('paginate')->with(10)->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->once()->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->campusHiring($request);

        $this->assertSame($mockResponse, $response);
    }

    #[Test]
    public function campus_hiring_page_filters_search_and_degree()
    {
        // TARGET: Cover logika if($search) dan if($degree) di method campusHiring()
        $request = Request::create('/campus-hiring', 'GET', [
            'search' => 'Mobile',
            'degree' => 'S1',
        ]);

        $this->campusHiringModelMock->shouldReceive('query')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('join')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('select')->andReturnSelf();

        // 1. Cover Search Closure
        $this->campusHiringModelMock->shouldReceive('where')
            ->with($this->callbackExecuteQueryClosure())
            ->andReturnSelf();

        // 2. Cover Degree
        $this->campusHiringModelMock->shouldReceive('whereJsonContains')
            ->with('t_campus_hiring.kualifikasi_pendidikan', 'S1')
            ->andReturnSelf();

        $this->campusHiringModelMock->shouldReceive('whereDate')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('orderBy')->andReturnSelf();
        $this->campusHiringModelMock->shouldReceive('paginate')->andReturn($this->createPaginatorMock());

        $mockResponse = Mockery::mock(Response::class);
        Inertia::shouldReceive('render')->andReturn($mockResponse);

        $controller = new LandingpageController;
        $response = $controller->campusHiring($request);

        $this->assertSame($mockResponse, $response);
    }

    // ==============================================
    // Test untuk method storeLamaran(Request $request)
    // ==============================================

    #[Test]
    public function store_lamaran_gagal_jika_tidak_login()
    {
        $request = Request::create('/lamaran', 'POST');
        // Tidak set attributes 'auth'

        $controller = new LandingpageController;
        $response = $controller->storeLamaran($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda harus login.', $response->getSession()->get('error'));
    }

    #[Test]
    public function store_lamaran_gagal_jika_validasi_gagal_atau_expired()
    {
        $authObj = (object) ['id' => 123];
        $request = Request::create('/lamaran', 'POST', [
            'id_campus_hiring' => 1,
            'nama_pelamar' => 'Test Pelamar',
            'url_cv' => 'http://cv.com',
        ]);
        $request->attributes->set('auth', $authObj);

        // Mock Find Campus Hiring
        $campusHiringObj = (object) ['batas_akhir' => '2020-01-01']; // Tanggal lampau
        $this->campusHiringModelMock->shouldReceive('find')->with(1)->andReturn($campusHiringObj);

        $controller = new LandingpageController;
        $response = $controller->storeLamaran($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Pendaftaran untuk lowongan ini sudah ditutup.', $response->getSession()->get('error'));
    }

    #[Test]
    public function store_lamaran_gagal_jika_sudah_pernah_mendaftar()
    {
        $authObj = (object) ['id' => 123];
        $request = Request::create('/lamaran', 'POST', [
            'id_campus_hiring' => 1,
            'nama_pelamar' => 'Test Pelamar',
            'url_cv' => 'http://cv.com',
        ]);
        $request->attributes->set('auth', $authObj);

        $campusHiringObj = (object) ['batas_akhir' => '2025-12-31'];
        $this->campusHiringModelMock->shouldReceive('find')->with(1)->andReturn($campusHiringObj);

        $this->lamaranCampusHiringModelMock->shouldReceive('where')->with('id_campus_hiring', 1)->andReturnSelf();
        $this->lamaranCampusHiringModelMock->shouldReceive('where')->with('user_id', 123)->andReturnSelf();
        $this->lamaranCampusHiringModelMock->shouldReceive('exists')->andReturn(true);

        $controller = new LandingpageController;
        $response = $controller->storeLamaran($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Anda sudah mendaftar di lowongan ini.', $response->getSession()->get('error'));
    }

    #[Test]
    public function store_lamaran_berhasil()
    {
        $authObj = (object) ['id' => 123];
        $request = Request::create('/lamaran', 'POST', [
            'id_campus_hiring' => 1,
            'nama_pelamar' => 'Test Pelamar',
            'url_cv' => 'http://cv.com',
        ]);
        $request->attributes->set('auth', $authObj);

        $campusHiringObj = (object) ['batas_akhir' => '2025-12-31'];
        $this->campusHiringModelMock->shouldReceive('find')->with(1)->andReturn($campusHiringObj);

        $this->lamaranCampusHiringModelMock->shouldReceive('where')->andReturnSelf();
        $this->lamaranCampusHiringModelMock->shouldReceive('where')->andReturnSelf();
        $this->lamaranCampusHiringModelMock->shouldReceive('exists')->andReturn(false);

        $this->lamaranCampusHiringModelMock->shouldReceive('create')->once()->andReturn(true);

        $controller = new LandingpageController;
        $response = $controller->storeLamaran($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('Lamaran berhasil dikirim!', $response->getSession()->get('success'));
    }
}
