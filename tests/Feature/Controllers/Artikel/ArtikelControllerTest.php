<?php

namespace Tests\Feature\Controllers\Artikel;

use App\Http\Controllers\App\Artikel\ArtikelController;
use App\Models\ArtikelModel;
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

class ArtikelControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        ArtikelModel::unguard();

        if (!Schema::hasTable('artikels')) {
            Schema::create('artikels', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->text('content');
                $table->string('category');
                $table->boolean('is_published')->default(false);
                $table->string('user_id')->nullable();
                $table->string('attachment')->nullable();
                $table->timestamps();
            });
        }

        $this->actingAs(User::factory()->create());
    }

    #[Test]
    public function index_menampilkan_halaman_artikel_dan_data()
    {
        ArtikelModel::create([
            'title' => 'Artikel Pertama',
            'content' => 'Isi konten pertama',
            'category' => 'Umum'
        ]);

        $response = $this->get(route('artikel'));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/artikel/artikel-page')
                ->has('artikelList.data', 1)
                ->where('artikelList.data.0.title', 'Artikel Pertama')
            );
    }

    #[Test]
    public function index_filter_pencarian_berfungsi()
    {
        ArtikelModel::create(['title' => 'Belajar Laravel', 'content' => 'Coding', 'category' => 'IT']);
        ArtikelModel::create(['title' => 'Resep Masakan', 'content' => 'Dapur', 'category' => 'Food']);

        $response = $this->get(route('artikel', ['search' => 'Laravel']));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('artikelList.data', 1)
                ->where('artikelList.data.0.title', 'Belajar Laravel')
            );
    }

    #[Test]
    public function show_menampilkan_detail_artikel_tanpa_attachment()
    {
        $artikel = ArtikelModel::create([
            'title' => 'Detail Title',
            'content' => 'Detail Content',
            'category' => 'News',
            'attachment' => null // Tidak ada file
        ]);

        $response = $this->get(route('artikel.detail', ['id' => $artikel->id]));

        $response->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('app/artikel/artikeldetailpage')
                ->where('artikel.title', 'Detail Title')
                ->where('artikel.attachment_url', null) // Cek else block
            );
    }

    /** * TEST BARU: Cek logic jika ada attachment (Coverage 100%)
     */
    #[Test]
    public function show_menampilkan_detail_artikel_dengan_attachment()
    {
        $artikel = ArtikelModel::create([
            'title' => 'With File',
            'content' => 'Content',
            'category' => 'News',
            'attachment' => 'dummy.pdf' // Pura-pura ada file
        ]);

        $this->get(route('artikel.detail', ['id' => $artikel->id]))
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('artikel.title', 'With File')
                ->whereNot('artikel.attachment_url', null) // Pastikan URL ter-generate (if block)
            );
    }

    #[Test]
    public function change_post_bisa_membuat_artikel_baru_dengan_attachment()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $data = [
            'title' => 'Artikel Baru',
            'content' => 'Isi Artikel Baru',
            'category' => 'Teknologi',
            'attachment' => $file
        ];

        $response = $this->post(route('artikel.change-post'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Artikel berhasil disimpan.');

        $this->assertDatabaseHas('artikels', [
            'title' => 'Artikel Baru',
            'category' => 'Teknologi'
        ]);

        $artikel = ArtikelModel::where('title', 'Artikel Baru')->first();
        $this->assertNotNull($artikel->attachment);
        Storage::disk('public')->assertExists($artikel->attachment);
    }

    #[Test]
    public function change_post_bisa_update_artikel()
    {
        $artikel = ArtikelModel::create([
            'title' => 'Judul Lama',
            'content' => 'Isi Lama',
            'category' => 'Old'
        ]);

        $data = [
            'id' => $artikel->id,
            'title' => 'Judul Update',
            'content' => 'Isi Update',
            'category' => 'New'
        ];

        $response = $this->post(route('artikel.change-post'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('artikels', [
            'id' => $artikel->id,
            'title' => 'Judul Update'
        ]);
    }

    #[Test]
    public function change_post_validasi_error()
    {
        $response = $this->post(route('artikel.change-post'), []);
        $response->assertSessionHasErrors(['title', 'content', 'category']);
    }

    #[Test]
    public function delete_post_menghapus_banyak_data_sekaligus()
    {
        $artikel1 = ArtikelModel::create(['title' => 'A', 'content' => 'A', 'category' => 'A']);
        $artikel2 = ArtikelModel::create(['title' => 'B', 'content' => 'B', 'category' => 'B']);
        $artikel3 = ArtikelModel::create(['title' => 'C', 'content' => 'C', 'category' => 'C']);

        $response = $this->post(route('artikel.delete-post'), [
            'artikelIds' => [$artikel1->id, $artikel2->id]
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Artikel berhasil dihapus.');

        $this->assertDatabaseMissing('artikels', ['id' => $artikel1->id]);
        $this->assertDatabaseMissing('artikels', ['id' => $artikel2->id]);
        $this->assertDatabaseHas('artikels', ['id' => $artikel3->id]);
    }

    /**
     * TEST BARU: Cek logic jika tidak ada ID yang dikirim (Coverage 100%)
     */
    #[Test]
    public function delete_post_tanpa_memilih_data()
    {
        // Kirim request tanpa artikelIds atau array kosong
        $response = $this->post(route('artikel.delete-post'), [
            'artikelIds' => [] 
        ]);

        $response->assertRedirect();
        // Pastikan tidak error, hanya redirect balik
    }
}