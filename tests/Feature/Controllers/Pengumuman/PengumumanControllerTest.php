<?php

namespace Tests\Feature\Controllers\Pengumuman;

use App\Http\Controllers\App\Pengumuman\PengumumanController;
use App\Models\PengumumanModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class PengumumanControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        PengumumanModel::unguard();

        // Pastikan tabel ada di database testing (SQLite)
        if (!Schema::hasTable('m_pengumuman')) {
            Schema::create('m_pengumuman', function (Blueprint $table) {
                $table->id();
                $table->string('judul');
                $table->text('isi');
                $table->date('expired_date');
                $table->string('gambar_path')->nullable(); 
                $table->timestamps();
            });
        }

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function index_menampilkan_halaman()
    {
        PengumumanModel::create(['judul' => 'A', 'isi' => 'A', 'expired_date' => now()]);

        $this->get(action([PengumumanController::class, 'index']))
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/pengumuman/pengumuman-page')
                ->has('data.data', 1)
            );
    }

    #[Test]
    public function index_search_filter()
    {
        PengumumanModel::create(['judul' => 'Cari Aku', 'isi' => 'x', 'expired_date' => now()]);
        PengumumanModel::create(['judul' => 'Abaikan', 'isi' => 'x', 'expired_date' => now()]);

        $this->get(action([PengumumanController::class, 'index'], ['search' => 'cari']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('data.data.0.judul', 'Cari Aku')
                ->has('data.data', 1)
            );
    }

    #[Test]
    public function post_change_simpan_sukses()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('p.jpg');

        $data = [
            'judul' => 'Judul P',
            'isi' => 'Isi P',
            'expired_date' => now()->addDay()->format('Y-m-d'),
            'gambar' => $file
        ];

        $this->post(action([PengumumanController::class, 'postChange']), $data)
            ->assertSessionHas('success');
            
        $this->assertDatabaseHas('m_pengumuman', ['judul' => 'Judul P']);
    }

    #[Test]
    public function post_change_validasi_error()
    {
        $response = $this->post(action([PengumumanController::class, 'postChange']), []);
        $response->assertSessionHasErrors(['judul', 'isi', 'expired_date']);
    }

    #[Test]
    public function post_change_ganti_gambar_lama()
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('uploads/pengumuman', 'public');
        
        $item = PengumumanModel::create([
            'judul' => 'Lama', 'isi' => 'Isi', 'expired_date' => now(), 'gambar_path' => $oldPath
        ]);

        $newFile = UploadedFile::fake()->image('new.jpg');
        $data = [
            'id' => $item->id,
            'judul' => 'Baru', 'isi' => 'Isi', 
            'expired_date' => now()->addDay()->format('Y-m-d'),
            'gambar' => $newFile // Upload gambar baru
        ];

        $this->post(action([PengumumanController::class, 'postChange']), $data)
            ->assertSessionHas('success');

        // Pastikan gambar lama terhapus
        Storage::disk('public')->assertMissing($oldPath);
        $item->refresh();
        Storage::disk('public')->assertExists($item->gambar_path);
    }

    #[Test]
    public function post_change_hapus_gambar_via_checkbox()
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('old.jpg')->store('uploads/pengumuman', 'public');
        
        $item = PengumumanModel::create([
            'judul' => 'Lama', 'isi' => 'Isi', 'expired_date' => now(), 'gambar_path' => $path
        ]);

        $data = [
            'id' => $item->id,
            'judul' => 'Baru', 'isi' => 'Isi', 
            'expired_date' => now()->addDay()->format('Y-m-d'),
            'delete_gambar' => true
        ];

        $this->post(action([PengumumanController::class, 'postChange']), $data);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('m_pengumuman', ['id' => $item->id, 'gambar_path' => null]);
    }

    #[Test]
    public function post_change_update_text_only()
    {
        // Test Case: Hanya update teks tanpa menyentuh gambar
        // Ini penting untuk coverage 100% (melewati if upload dan elseif delete)
        $item = PengumumanModel::create([
            'judul' => 'Awal', 
            'isi' => 'Awal', 
            'expired_date' => now()
        ]);

        $data = [
            'id' => $item->id,
            'judul' => 'Ubah',
            'isi' => 'Ubah',
            'expired_date' => now()->addDay()->format('Y-m-d'),
        ];

        $this->post(action([PengumumanController::class, 'postChange']), $data)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('m_pengumuman', [
            'id' => $item->id,
            'judul' => 'Ubah'
        ]);
    }

    #[Test]
    public function post_change_general_exception()
    {
        // 1. Simulasikan Error pada Storage
        Storage::shouldReceive('disk')
            ->andThrow(new \Exception('Storage Error Simulated'));

        // 2. PENTING: Data harus punya gambar_path
        // Agar controller mencoba memanggil Storage::delete() dan memicu Exception di atas
        $item = PengumumanModel::create([
            'judul' => 'x', 'isi' => 'x', 'expired_date' => now(), 
            'gambar_path' => 'dummy.jpg' 
        ]);

        $data = [
            'id' => $item->id,
            'judul' => 'Judul', 'isi' => 'Isi', 
            'expired_date' => now()->addDay()->format('Y-m-d'),
            'delete_gambar' => true // Ini trigger logika hapus -> Panggil Storage -> Exception
        ];

        $response = $this->post(action([PengumumanController::class, 'postChange']), $data);
        
        $response->assertSessionHas('error');
    }

    #[Test]
    public function post_delete_menghapus_data()
    {
        Mockery::close(); // Reset mock dari test sebelumnya
        Storage::fake('public');
        $path = UploadedFile::fake()->image('del.jpg')->store('uploads/pengumuman', 'public');

        $item = PengumumanModel::create([
            'judul' => 'Hapus', 'isi' => 'x', 'expired_date' => now(), 'gambar_path' => $path
        ]);

        $this->post(action([PengumumanController::class, 'postDelete']), ['id' => $item->id]);

        $this->assertModelMissing($item);
        Storage::disk('public')->assertMissing($path);
    }
}