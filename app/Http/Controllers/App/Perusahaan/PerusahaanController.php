<?php

namespace App\Http\Controllers\App\Perusahaan;

use App\Helper\ConstHelper;
use App\Helper\ToolsHelper;
use App\Http\Controllers\Controller;
use App\Models\PerusahaanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PerusahaanController extends Controller
{
    public function index(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);
        $search = $request->query('search', '');
        $page = $request->query('page', 1);
        $perPage = $request->query('perPage', 5);
        if ($perPage <= 0) {
            $perPage = 5;
        }

        $perPageOptions = ConstHelper::OPTION_ROWS_PER_PAGE;

        return Inertia::render('app/perusahaan/perusahaan-page', [
            // LAZY: hanya dipanggil jika dibutuhkan di sisi front-end
            'perusahaanList' => fn () => PerusahaanModel::query()
                ->when($search, function ($query) use ($search) {
                    $lower = strtolower($search);

                    $query->where(fn ($q) => $q
                        ->whereRaw('LOWER(nama) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(industri) LIKE ?', ["%{$lower}%"]));
                })
                ->orderByDesc('created_at')
                ->paginate($perPage),
            // ALWAYS: selalu dikirim (meskipun lazy props tidak dipanggil)
            'pageName' => Inertia::always('Daftar Perusahaan'),
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
        // Cek izin
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);
        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah data perusahaan.');
        }

        $request->validate([
            'nama' => 'required|string|max:255',
            'lokasi' => 'required|string',
            'website' => 'required|string',
            'industri' => 'required|string|max:100',
            'deskripsi' => 'required|string',
            'url_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->filled('id_perusahaan')) {
            // --- LOGIKA UPDATE ---
            $perusahaan = PerusahaanModel::where('id_perusahaan', $request->id_perusahaan)->first();
            if (! $perusahaan) {
                return back()->with('error', 'Data Perusahaan tidak ditemukan.');
            }

            $perusahaan->nama = $request->nama;
            $perusahaan->lokasi = $request->lokasi;
            $perusahaan->website = $request->website;
            $perusahaan->industri = $request->industri;
            $perusahaan->deskripsi = $request->deskripsi;

            // Cek upload file baru
            if ($request->hasFile('url_logo')) {
                // Simpan file baru
                $path = $request->file('url_logo')->store('uploads/logos', 'public');
                $perusahaan->url_logo = '/storage/'.$path;
            }

            $perusahaan->save();

            return back()->with('success', 'Data Perusahaan berhasil diperbarui.');
        } else {
            // --- LOGIKA CREATE ---
            $urlLogo = null;

            if ($request->hasFile('url_logo')) {
                $path = $request->file('url_logo')->store('uploads/logos', 'public');
                $urlLogo = '/storage/'.$path;
            }

            PerusahaanModel::create([
                'id_perusahaan' => ToolsHelper::generateId(),
                'nama' => $request->nama,
                'lokasi' => $request->lokasi,
                'website' => $request->website,
                'industri' => $request->industri,
                'deskripsi' => $request->deskripsi,
                'url_logo' => $urlLogo,
            ]);

            return back()->with('success', 'Perusahaan Baru berhasil ditambahkan.');
        }
    }

    public function postDelete(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);

        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk menghapus data.');
        }

        $request->validate([
            'ids' => 'required|array',
        ]);

        $perusahaans = PerusahaanModel::whereIn('id_perusahaan', $request->ids)->get();

        DB::transaction(function () use ($perusahaans) {
            foreach ($perusahaans as $perusahaan) {
                if ($perusahaan->url_logo) {
                    $path = str_replace('/storage/', '', $perusahaan->url_logo);

                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }

                $perusahaan->delete();
            }
        });

        return back()->with('success', 'Data Perusahaan yang dipilih berhasil dihapus.');
    }

    private function checkIsEditor($auth)
    {
        if (ToolsHelper::checkRoles('Perusahaan', $auth->akses)) {
            return true;
        }

        return false;
    }
}
