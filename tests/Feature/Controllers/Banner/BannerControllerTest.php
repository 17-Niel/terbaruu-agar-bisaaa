<?php

namespace Tests\Feature\Controllers\Banner;

use App\Helper\ToolsHelper;
use App\Http\Controllers\App\Banner\BannerController;
use App\Models\BannerModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Mockery;
use PHPUnit\Framework\Assert; // Import PHPUnit Assert
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BannerControllerTest extends TestCase
{
    protected $bannerMock;

    protected $toolsHelperMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Fake Storage 'public'
        Storage::fake('public');

        // 2. Mock Inertia::always
        Inertia::shouldReceive('always')
            ->andReturnUsing(function ($value) {
                return Mockery::mock('overload:Inertia\AlwaysProp', [
                    'getValue' => $value,
                ]);
            });

        // 3. Mock Model dengan alias
        $this->bannerMock = Mockery::mock('alias:'.BannerModel::class);

        // 4. Mock Helper Static
        $this->toolsHelperMock = Mockery::mock('alias:'.ToolsHelper::class);

        // 5. Mock DB Transaction default (digunakan untuk skenario SUCCESS)
        DB::shouldReceive('transaction')
            ->byDefault()
            ->andReturnUsing(function ($callback) {
                return $callback(); // Selalu jalankan closure
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
    public function index_render_default()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner', 'GET');
        $request->attributes->set('auth', $auth);

        $this->toolsHelperMock->shouldReceive('checkRoles')
            ->with('Banner', $auth->akses)
            ->andReturn(true);

        $this->bannerMock->shouldReceive('query')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('when')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('orderBy')->with('urutan', 'asc')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('paginate')->with(5)->andReturn('paginateData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/banner/banner-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        $controller = new BannerController;
        $res = $controller->index($request);

        $this->assertSame($mockResp, $res);
        $this->assertEquals('paginateData', $captured['bannerList']());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_with_search()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner', 'GET', ['search' => 'promo']);
        $request->attributes->set('auth', $auth);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);
        $this->bannerMock->shouldReceive('query')->andReturn($this->bannerMock);

        $this->bannerMock->shouldReceive('when')
            ->once()
            ->with('promo', Mockery::type('callable'))
            ->andReturnUsing(function ($search, $callback) {
                $callback($this->bannerMock, $search);

                return $this->bannerMock;
            });

        $this->bannerMock->shouldReceive('where')
            ->once()
            ->with(Mockery::type('callable'))
            ->andReturnUsing(function ($cb) {
                $cb($this->bannerMock);

                return $this->bannerMock;
            });

        $this->bannerMock->shouldReceive('whereRaw')
            ->with('LOWER(nama_banner) LIKE ?', ['%promo%'])
            ->andReturn($this->bannerMock);

        $this->bannerMock->shouldReceive('orWhereRaw')
            ->with('LOWER(url_gambar) LIKE ?', ['%promo%'])
            ->andReturn($this->bannerMock);

        $this->bannerMock->shouldReceive('orderBy')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('paginate')->with(5)->andReturn('searchData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/banner/banner-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        (new BannerController)->index($request);
        $this->assertEquals('searchData', $captured['bannerList']());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_with_invalid_per_page_uses_default()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner', 'GET', ['perPage' => 0]);
        $request->attributes->set('auth', $auth);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        $this->bannerMock->shouldReceive('query')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('when')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('orderBy')->with('urutan', 'asc')->andReturn($this->bannerMock);

        $this->bannerMock->shouldReceive('paginate')->with(5)->andReturn('defaultPerPageData');

        $captured = [];
        $mockResp = Mockery::mock(Response::class);

        Inertia::shouldReceive('render')
            ->once()
            ->with('app/banner/banner-page', Mockery::capture($captured))
            ->andReturn($mockResp);

        (new BannerController)->index($request);
        $this->assertEquals('defaultPerPageData', $captured['bannerList']());
        $this->assertEquals(5, $captured['perPage']->getValue());
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function index_fails_without_access()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/banner', 'GET');
        $request->attributes->set('auth', $auth);

        $this->toolsHelperMock->shouldReceive('checkRoles')
            ->with('Banner', $auth->akses)
            ->andReturn(false); // No access

        (new BannerController)->index($request);

        $this->assertEquals('Anda tidak memiliki izin untuk mengolah banner.', session('error'));
    }

    // =========================================================================
    // TEST METHOD: postChange()
    // =========================================================================

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_fails_without_access()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $this->toolsHelperMock->shouldReceive('checkRoles')
            ->with('Banner', $auth->akses)
            ->andReturn(false);

        (new BannerController)->postChange($request);

        $this->assertEquals(
            'Anda tidak memiliki izin untuk mengolah banner.',
            session('error')
        );
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_creates_new_banner()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $file = UploadedFile::fake()->image('banner.jpg');
        $request->files->set('gambar', $file);

        $request->merge([
            'nama_banner' => 'Banner Baru',
            'shown' => '1',
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);
        $this->bannerMock->shouldReceive('max')->with('urutan')->andReturn(5);

        $this->bannerMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['nama_banner'] === 'Banner Baru'
                    && $data['urutan'] === 6
                    && str_contains($data['url_gambar'], '/storage/banners/')
                    && $data['shown'] == '1';
            }))
            ->andReturnTrue();

        (new BannerController)->postChange($request);

        $this->assertEquals('Banner berhasil ditambahkan.', session('success'));
        // FIX: Use PHPUnit's Assert::assertCount for reliability
        Assert::assertCount(1, Storage::disk('public')->files('banners'), 'Expected 1 file in the banners directory after creation.');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_create_fails_if_image_missing()
    {
        $this->expectException(ValidationException::class);

        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'nama_banner' => 'Test Name',
            'shown' => '1',
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);
        $this->bannerMock->shouldNotReceive('create');

        (new BannerController)->postChange($request);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_updates_existing_banner_with_new_image()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $file = UploadedFile::fake()->image('new-banner.jpg');
        $request->files->set('gambar', $file);

        $request->merge([
            'bannerId' => 'uuid-123',
            'nama_banner' => 'Updated Banner',
            'shown' => '0',
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        // Setup the old image file in storage
        Storage::disk('public')->put('banners/old.jpg', 'content');
        Storage::disk('public')->assertExists('banners/old.jpg');

        $existingBanner = Mockery::mock();
        $existingBanner->id_banner = 'uuid-123';
        $existingBanner->url_gambar = '/storage/banners/old.jpg'; // Has an old URL
        $existingBanner->shouldIgnoreMissing();
        $existingBanner->shouldReceive('save')->once()->andReturnTrue();

        $this->bannerMock->shouldReceive('where')
            ->with('id_banner', 'uuid-123')
            ->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('first')->andReturn($existingBanner);

        (new BannerController)->postChange($request);

        $this->assertEquals('Banner berhasil diperbarui.', session('success'));

        // Assert old file was deleted and new file was created
        Storage::disk('public')->assertMissing('banners/old.jpg');
        // FIX: Use PHPUnit's Assert::assertCount for reliability (1 file remaining: the new one)
        Assert::assertCount(1, Storage::disk('public')->files('banners'), 'Expected 1 file in the banners directory after update.');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_updates_with_new_image_but_no_old_image_url()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $file = UploadedFile::fake()->image('new-banner.jpg');
        $request->files->set('gambar', $file);

        $request->merge([
            'bannerId' => 'uuid-123',
            'nama_banner' => 'Updated Banner',
            'shown' => '0',
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        // Check storage starts empty
        Assert::assertCount(0, Storage::disk('public')->files('banners'));

        $existingBanner = Mockery::mock();
        $existingBanner->id_banner = 'uuid-123';
        $existingBanner->url_gambar = null; // Set old URL to null
        $existingBanner->shouldIgnoreMissing();
        $existingBanner->shouldReceive('save')->once()->andReturnTrue();

        $this->bannerMock->shouldReceive('where')
            ->with('id_banner', 'uuid-123')
            ->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('first')->andReturn($existingBanner);

        (new BannerController)->postChange($request);

        $this->assertEquals('Banner berhasil diperbarui.', session('success'));

        // FIX: The test must assert 1 file exists (the newly uploaded one)
        Assert::assertCount(1, Storage::disk('public')->files('banners'), 'Expected 1 file in the banners directory after update with new image.');
        $this->assertStringContainsString('/storage/banners/', $existingBanner->url_gambar);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_updates_existing_banner_without_new_image()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'bannerId' => 'uuid-456',
            'nama_banner' => 'Updated No Image',
            'shown' => '1',
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        $existingBanner = Mockery::mock();
        $existingBanner->id_banner = 'uuid-456';
        $existingBanner->url_gambar = '/storage/banners/original.png';
        $existingBanner->shouldIgnoreMissing();
        $existingBanner->shouldReceive('save')->once()->andReturnTrue();

        $this->bannerMock->shouldReceive('where')
            ->with('id_banner', 'uuid-456')
            ->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('first')->andReturn($existingBanner);

        (new BannerController)->postChange($request);

        $this->assertEquals('Banner berhasil diperbarui.', session('success'));
        $this->assertEquals('Updated No Image', $existingBanner->nama_banner);
        $this->assertEquals('/storage/banners/original.png', $existingBanner->url_gambar);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_change_fails_if_banner_not_found()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/change', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'bannerId' => 'id-not-found',
            'nama_banner' => 'Test Name',
            'shown' => '1',
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        $this->bannerMock->shouldReceive('where')
            ->with('id_banner', 'id-not-found')
            ->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('first')->andReturn(null);

        (new BannerController)->postChange($request);

        $this->assertEquals('Banner tidak ditemukan.', session('error'));
    }

    // =========================================================================
    // TEST METHOD: postDelete()
    // =========================================================================

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_gagal_tanpa_akses()
    {
        $auth = (object) ['id' => 1, 'akses' => ['User']];
        $request = Request::create('/banner/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(false);

        (new BannerController)->postDelete($request);

        $this->assertEquals('Anda tidak memiliki izin untuk mengolah banner.', session('error'));
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_success()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'bannerIds' => ['uuid-1', 'uuid-2'],
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        Storage::disk('public')->put('banners/img1.jpg', 'content');
        Storage::disk('public')->assertExists('banners/img1.jpg');

        $banner1 = Mockery::mock()->shouldIgnoreMissing();
        $banner1->url_gambar = '/storage/banners/img1.jpg'; // Has image
        $banner1->shouldReceive('delete')->once();

        $banner2 = Mockery::mock()->shouldIgnoreMissing();
        $banner2->url_gambar = null; // No image
        $banner2->shouldReceive('delete')->once();

        $this->bannerMock->shouldReceive('whereIn')
            ->with('id_banner', ['uuid-1', 'uuid-2'])
            ->andReturn($this->bannerMock);

        $this->bannerMock->shouldReceive('get')->andReturn(collect([$banner1, $banner2]));

        (new BannerController)->postDelete($request);

        $this->assertEquals('Banner yang dipilih berhasil dihapus.', session('success'));

        Storage::disk('public')->assertMissing('banners/img1.jpg');
        // FIX: Use PHPUnit's Assert::assertCount for reliability (0 files remaining)
        Assert::assertCount(0, Storage::disk('public')->files('banners'), 'Expected 0 files in the banners directory after delete.');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_success_with_null_image_url()
    {
        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'bannerIds' => ['uuid-no-image'],
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        Assert::assertCount(0, Storage::disk('public')->files('banners'));

        $bannerNoImage = Mockery::mock()->shouldIgnoreMissing();
        $bannerNoImage->url_gambar = null; // The critical test case
        $bannerNoImage->shouldReceive('delete')->once();

        $this->bannerMock->shouldReceive('whereIn')
            ->with('id_banner', ['uuid-no-image'])
            ->andReturn($this->bannerMock);

        $this->bannerMock->shouldReceive('get')->andReturn(collect([$bannerNoImage]));

        (new BannerController)->postDelete($request);

        $this->assertEquals('Banner yang dipilih berhasil dihapus.', session('success'));

        // FIX: Use PHPUnit's Assert::assertCount for reliability (0 files remaining)
        Assert::assertCount(0, Storage::disk('public')->files('banners'), 'Expected 0 files in the banners directory after delete.');
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function post_delete_fails_on_db_exception()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Database Error Mocked');

        $auth = (object) ['id' => 1, 'akses' => ['Banner']];
        $request = Request::create('/banner/delete', 'POST');
        $request->attributes->set('auth', $auth);

        $request->merge([
            'bannerIds' => ['uuid-fail'],
        ]);

        $this->toolsHelperMock->shouldReceive('checkRoles')->andReturn(true);

        $bannerFail = Mockery::mock()->shouldIgnoreMissing();
        $bannerFail->url_gambar = null;

        $this->bannerMock->shouldReceive('whereIn')->andReturn($this->bannerMock);
        $this->bannerMock->shouldReceive('get')->andReturn(collect([$bannerFail]));

        // Override mock DB::transaction untuk throw exception
        DB::shouldReceive('transaction')
            ->once()
            ->with(Mockery::type('callable'))
            ->andThrow(new Exception('Database Error Mocked'));

        (new BannerController)->postDelete($request);
    }
}
