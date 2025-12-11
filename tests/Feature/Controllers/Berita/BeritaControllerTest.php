<?php

namespace Tests\Feature\Controllers\Berita;

use App\Http\Controllers\App\Berita\BeritaController;
use App\Models\BeritaModel;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BeritaControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        BeritaModel::unguard();

        // 1. Sesuaikan Schema dengan BeritaController (isi, status, tanggal, author, gambar)
        if (! Schema::hasTable('m_berita')) {
            Schema::create('m_berita', function (Blueprint $table) {
                $table->id('id_berita'); // Primary Key
                $table->string('judul');
                $table->text('isi');     // Sesuai Controller
                $table->string('status'); // Sesuai Controller
                $table->dateTime('tanggal')->nullable();
                $table->string('author')->nullable();
                $table->string('gambar')->nullable(); // Sesuai Controller
                $table->timestamps();
            });
        }

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function index_menampilkan_halaman_berita_dan_filter()
    {
        // Setup Data
        BeritaModel::create([
            'judul' => 'Berita A',
            'isi' => 'Konten A',
            'status' => 'published',
            'tanggal' => now(),
        ]);
        BeritaModel::create([
            'judul' => 'Info B',
            'isi' => 'Konten B',
            'status' => 'draft',
            'tanggal' => now(),
        ]);

        // 1. Test Index (Tanpa Filter)
        $this->get(action([BeritaController::class, 'index']))
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/berita/berita-page')
                ->has('berita.data', 2) // Controller me-return props 'berita'
                ->has('stats')
            );

        // 2. Test Filter Search
        $this->get(action([BeritaController::class, 'index'], ['search' => 'Info']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('berita.data', 1)
                ->where('berita.data.0.judul', 'Info B')
            );

        // 3. Test Filter Status
        $this->get(action([BeritaController::class, 'index'], ['status' => 'published']))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('berita.data', 1)
                ->where('berita.data.0.status', 'published')
            );
    }

    #[Test]
    public function post_change_simpan_berita_baru()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('news.jpg');

        $data = [
            'judul' => 'Judul Baru',
            'isi' => 'Isi Berita',
            'status' => 'published', // Wajib ada karena validasi 'required'
            'tanggal' => now()->format('Y-m-d'),
            'author' => 'Tester',
            'gambar' => $file,
        ];

        // Panggil method postChange (sesuai controller)
        $response = $this->post(action([BeritaController::class, 'postChange']), $data);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Data berhasil disimpan');

        $this->assertDatabaseHas('m_berita', ['judul' => 'Judul Baru']);

        $berita = BeritaModel::where('judul', 'Judul Baru')->first();
        $this->assertNotNull($berita->gambar);
        Storage::disk('public')->assertExists($berita->gambar);
    }

    #[Test]
    public function post_change_update_berita_dan_hapus_gambar()
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('old.jpg')->store('berita', 'public');

        $berita = BeritaModel::create([
            'judul' => 'Lama',
            'isi' => 'Isi',
            'status' => 'draft',
            'gambar' => $path,
        ]);

        $data = [
            'id' => $berita->id_berita, // ID untuk update
            'judul' => 'Baru',
            'isi' => 'Isi Baru',
            'status' => 'published',
            'delete_gambar' => true, // Trigger hapus gambar
        ];

        $this->post(action([BeritaController::class, 'postChange']), $data);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('m_berita', ['id_berita' => $berita->id_berita, 'gambar' => null]);
    }

    #[Test]
    public function post_change_upload_gambar_baru_saat_edit()
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old.jpg')->store('berita', 'public');

        $berita = BeritaModel::create([
            'judul' => 'Lama', 'isi' => 'Isi', 'status' => 'draft', 'gambar' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->image('new.jpg');
        $data = [
            'id' => $berita->id_berita,
            'judul' => 'Lama', 'isi' => 'Isi', 'status' => 'draft',
            'gambar' => $newFile,
        ];

        $this->post(action([BeritaController::class, 'postChange']), $data);

        Storage::disk('public')->assertMissing($oldPath);
        $berita->refresh();
        Storage::disk('public')->assertExists($berita->gambar);
    }

    #[Test]
    public function post_delete_menghapus_data()
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('del.jpg')->store('berita', 'public');

        $berita = BeritaModel::create([
            'judul' => 'Hapus', 'isi' => 'x', 'status' => 'draft', 'gambar' => $path,
        ]);

        // Panggil method postDelete (sesuai controller)
        $this->post(action([BeritaController::class, 'postDelete']), ['id' => $berita->id_berita])
            ->assertRedirect();

        $this->assertModelMissing($berita);
        Storage::disk('public')->assertMissing($path);
    }
}
