<?php

namespace App\Http\Controllers\App\LowonganPekerjaan;

use App\Helper\ConstHelper;
use App\Helper\ToolsHelper;
use App\Http\Controllers\Controller;
// use App\Models\JenisLowonganModel; // TIDAK DIPAKAI LAGI
use App\Models\LowonganPekerjaanModel;
use App\Models\PerusahaanModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // Tambahkan ini untuk validasi List
use Inertia\Inertia;

class LowonganPekerjaanController extends Controller
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

        $lowonganList = LowonganPekerjaanModel::query()
            ->where('id_admin_pembuat', $auth->id)
            ->when($search, function ($query) use ($search) {
                $lower = strtolower($search);
                $query->where(function ($q) use ($lower) {
                    $q->whereRaw('LOWER(nama_lowongan) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(deskripsi) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(departemen) LIKE ?', ["%{$lower}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return Inertia::render('app/lowongan-pekerjaan/lowongan-pekerjaan-page', [
            'lowonganList' => fn () => $lowonganList,
            'perusahaanList' => PerusahaanModel::all(),

            // PERUBAHAN: Mengirim array string statis, bukan query database
            'jenisLowonganList' => LowonganPekerjaanModel::getJenisOptions(),

            'pageName' => Inertia::always('Daftar Lowongan Pekerjaan'),
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
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah lowongan pekerjaan.');
        }

        // Ambil daftar opsi valid dari Model
        $validJenis = LowonganPekerjaanModel::getJenisOptions();

        $request->validate([
            'id_perusahaan' => 'required|string',

            // PERUBAHAN: Validasi string harus salah satu dari statis options
            'jenis_lowongan' => ['required', 'string', Rule::in($validJenis)],

            'nama_lowongan' => 'required|string|max:255',
            'departemen' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'kualifikasi' => 'nullable|string',
            'benefit' => 'nullable|string',
            'kualifikasi_pendidikan' => 'nullable|array',
            'link_pendaftaran' => 'nullable|url',
            'batas_akhir' => 'nullable|date',
        ]);

        // --- UPDATE MODE ---
        if (isset($request->id_lowongan) && ! empty($request->id_lowongan)) {
            $lowongan = LowonganPekerjaanModel::where('id_lowongan', $request->id_lowongan)
                ->where('id_admin_pembuat', $auth->id)
                ->first();

            if (! $lowongan) {
                return back()->with('error', 'Lowongan tidak ditemukan atau Anda tidak memiliki akses.');
            }

            $lowongan->id_perusahaan = $request->id_perusahaan;

            // PERUBAHAN: Simpan string langsung
            $lowongan->jenis_lowongan = $request->jenis_lowongan;

            $lowongan->nama_lowongan = $request->nama_lowongan;
            $lowongan->departemen = $request->departemen;
            $lowongan->deskripsi = $request->deskripsi;
            $lowongan->kualifikasi = $request->kualifikasi;
            $lowongan->benefit = $request->benefit;
            $lowongan->kualifikasi_pendidikan = $request->kualifikasi_pendidikan;
            $lowongan->link_pendaftaran = $request->link_pendaftaran;
            $lowongan->batas_akhir = $request->batas_akhir;

            $lowongan->save();

            return back()->with('success', 'Lowongan Pekerjaan berhasil diperbarui.');
        }

        // --- CREATE MODE ---
        LowonganPekerjaanModel::create([
            'id_perusahaan' => $request->id_perusahaan,
            'id_admin_pembuat' => $auth->id,

            // PERUBAHAN: Simpan string langsung
            'jenis_lowongan' => $request->jenis_lowongan,

            'nama_lowongan' => $request->nama_lowongan,
            'departemen' => $request->departemen,
            'deskripsi' => $request->deskripsi,
            'kualifikasi' => $request->kualifikasi,
            'benefit' => $request->benefit,
            'kualifikasi_pendidikan' => $request->kualifikasi_pendidikan,
            'link_pendaftaran' => $request->link_pendaftaran,
            'batas_akhir' => $request->batas_akhir,
        ]);

        return back()->with('success', 'Lowongan Pekerjaan berhasil ditambahkan.');
    }

    public function postDelete(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);

        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah lowongan pekerjaan.');
        }

        $request->validate([
            'ids_lowongan' => 'required|array',
        ]);

        LowonganPekerjaanModel::whereIn('id_lowongan', $request->ids_lowongan)
            ->where('id_admin_pembuat', $auth->id)
            ->delete();

        return back()->with('success', 'Lowongan pekerjaan yang dipilih berhasil dihapus.');
    }

    private function checkIsEditor($auth)
    {
        return ToolsHelper::checkRoles('Lowongan Pekerjaan', $auth->akses);
    }
}
