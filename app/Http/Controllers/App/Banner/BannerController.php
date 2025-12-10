<?php

namespace App\Http\Controllers\App\Banner;

use App\Helper\ConstHelper;
use App\Helper\ToolsHelper;
use App\Http\Controllers\Controller;
use App\Models\BannerModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BannerController extends Controller
{
    public function index(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);

        // === SECURITY CHECK ===
        // Jika user tidak punya akses 'Banner', redirect ke dashboard
        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah banner.');
        }

        $search = $request->query('search', '');
        $page = $request->query('page', 1);
        $perPage = $request->query('perPage', 5);

        if ($perPage <= 0) {
            $perPage = 5;
        }

        $perPageOptions = ConstHelper::OPTION_ROWS_PER_PAGE;

        return Inertia::render('app/banner/banner-page', [
            // LAZY: Data hanya diambil jika diminta oleh frontend
            'bannerList' => fn () => BannerModel::query()
                    // Filter pencarian (berdasarkan Nama Banner ATAU URL gambar)
                ->when($search, function ($query) use ($search) {
                    $lower = strtolower($search);
                    $query->where(function ($q) use ($lower) {
                        $q->whereRaw('LOWER(nama_banner) LIKE ?', ["%{$lower}%"])
                            ->orWhereRaw('LOWER(url_gambar) LIKE ?', ["%{$lower}%"]);
                    });
                })
                    // Urutkan berdasarkan 'urutan' agar tampil rapi
                ->orderBy('urutan', 'asc')
                ->paginate($perPage),

            // ALWAYS: Data ini selalu dikirim ke frontend
            'pageName' => Inertia::always('Daftar Banner'),
            'auth' => Inertia::always($auth),
            'isEditor' => Inertia::always($isEditor),
            'search' => Inertia::always($search),
            'page' => Inertia::always($page),
            'perPage' => Inertia::always($perPage),
            'perPageOptions' => Inertia::always($perPageOptions),
        ]);
    }

    public function postChange(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);

        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah banner.');
        }

        // Validasi input
        $request->validate([
            'nama_banner' => 'required|string|max:255', // Validasi Nama Banner
            'gambar' => 'nullable|image|max:2048', // Max 2MB
            'shown' => 'required|boolean',
        ]);

        // Cek apakah ini Update atau Create
        if (isset($request->bannerId) && ! empty($request->bannerId)) {
            // === MODE EDIT ===
            $banner = BannerModel::where('id_banner', $request->bannerId)->first();

            if (! $banner) {
                return back()->with('error', 'Banner tidak ditemukan.');
            }

            // Jika ada file gambar baru diupload
            if ($request->hasFile('gambar')) {
                // Hapus gambar lama dari storage jika ada
                if ($banner->url_gambar) {
                    // Convert URL public ke path storage relative
                    $oldPath = str_replace('/storage/', '', $banner->url_gambar);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                // Simpan gambar baru
                $path = $request->file('gambar')->store('banners', 'public');
                $banner->url_gambar = '/storage/'.$path;
            }

            // Update data
            $banner->nama_banner = $request->nama_banner;
            $banner->shown = $request->shown;
            $banner->save();

            return back()->with('success', 'Banner berhasil diperbarui.');

        } else {
            // === MODE TAMBAH (CREATE) ===

            // Gambar wajib ada saat membuat baru
            $request->validate([
                'gambar' => 'required|image|max:2048',
            ]);

            // Hitung urutan otomatis (Increment)
            $lastOrder = BannerModel::max('urutan') ?? 0;

            // Simpan gambar
            $path = $request->file('gambar')->store('banners', 'public');

            BannerModel::create([
                'nama_banner' => $request->nama_banner,
                'urutan' => $lastOrder + 1,
                'url_gambar' => '/storage/'.$path,
                'shown' => $request->shown,
            ]);

            return back()->with('success', 'Banner berhasil ditambahkan.');
        }
    }

    public function postDelete(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);

        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah banner.');
        }

        $request->validate([
            'bannerIds' => 'required|array',
            // PENTING: Gunakan 'string' karena UUID adalah string, bukan integer
            'bannerIds.*' => 'string',
        ]);

        $banners = BannerModel::whereIn('id_banner', $request->bannerIds)->get();

        // Gunakan Transaction untuk memastikan konsistensi
        DB::transaction(function () use ($banners) {
            foreach ($banners as $banner) {
                // Hapus file gambar jika ada
                if ($banner->url_gambar) {
                    // Ubah URL publik menjadi path relative storage
                    $path = str_replace('/storage/', '', $banner->url_gambar);

                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }

                // Hapus record banner dari database
                $banner->delete();
            }
        });

        return back()->with('success', 'Banner yang dipilih berhasil dihapus.');
    }

    private function checkIsEditor($auth)
    {
        // Pastikan role sesuai dengan yang ada di database Anda ('Banner' atau 'Homepage')
        if (ToolsHelper::checkRoles('Banner', $auth->akses)) {
            return true;
        }

        return false;
    }
}
