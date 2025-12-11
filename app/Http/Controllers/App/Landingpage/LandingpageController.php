<?php

namespace App\Http\Controllers\App\Landingpage;

use App\Http\Controllers\Controller;
use App\Models\BannerModel;
use App\Models\CampusHiringModel;
use App\Models\LamaranCampusHiringModel;
use App\Models\LowonganPekerjaanModel;
use App\Models\PerusahaanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class LandingpageController extends Controller
{
    // --- HELPER AUTH ---
    private function getAuthData($request)
    {
        $auth = $request->attributes->get('auth');

        return $auth ? [
            'id' => $auth->id,
            'nama' => $auth->name,
            'username' => $auth->username,
            'role' => $auth->alias ?? 'user',
            'akses' => $auth->akses ?? [],
            'photo' => $auth->photo ?? null,
        ] : null;
    }

    public function index(Request $request)
    {
        $authData = $this->getAuthData($request);
        $activeTab = $request->query('tab', 'lowongan');
        $search = $request->query('search', '');
        $degree = $request->query('degree', 'All Degree');

        $banners = BannerModel::where('shown', true)->orderBy('urutan', 'asc')->get();
        $contentData = null;
        $degreeOptions = ['D3', 'D4', 'S1', 'S2', 'S3'];

        $toleranceDate = Carbon::now()->subDays(3);
        $activeDate = Carbon::today();

        switch ($activeTab) {
            case 'campus-hiring':
                $query = CampusHiringModel::query();
                $query->join('m_perusahaan', 't_campus_hiring.id_perusahaan', '=', 'm_perusahaan.id_perusahaan')
                    ->select('t_campus_hiring.*', 'm_perusahaan.nama as nama_perusahaan', 'm_perusahaan.url_logo', 'm_perusahaan.lokasi');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('t_campus_hiring.nama_campus_hiring', 'LIKE', "%{$search}%")
                            ->orWhere('m_perusahaan.nama', 'LIKE', "%{$search}%");
                    });
                }
                if ($degree !== 'All Degree' && in_array($degree, $degreeOptions)) {
                    $query->whereJsonContains('t_campus_hiring.kualifikasi_pendidikan', $degree);
                }
                $query->whereDate('t_campus_hiring.batas_akhir', '>=', $toleranceDate);
                $contentData = $query->orderBy('t_campus_hiring.created_at', 'desc')->paginate(9)->withQueryString();
                break;

            case 'perusahaan':
                $query = PerusahaanModel::query();
                if ($search) {
                    $query->where('nama', 'LIKE', "%{$search}%");
                }
                $companies = $query->orderBy('nama', 'asc')->paginate(9)->withQueryString();

                // Ganti $toleranceDate dengan $activeDate di sini
                $companies->getCollection()->transform(function ($company) use ($activeDate) {
                    $countLowongan = LowonganPekerjaanModel::where('id_perusahaan', $company->id_perusahaan)
                        ->whereDate('batas_akhir', '>=', $activeDate)->count(); // Hitung yang belum expired

                    $countCampus = CampusHiringModel::where('id_perusahaan', $company->id_perusahaan)
                        ->whereDate('batas_akhir', '>=', $activeDate)->count();

                    $company->total_jobs = $countLowongan + $countCampus;

                    return $company;
                });
                $contentData = $companies;
                break;

            case 'lowongan':
            default:
                $query = LowonganPekerjaanModel::query();
                $query->join('m_perusahaan', 't_lowongan_pekerjaan.id_perusahaan', '=', 'm_perusahaan.id_perusahaan')
                    ->select('t_lowongan_pekerjaan.*', 'm_perusahaan.nama as nama_perusahaan', 'm_perusahaan.url_logo', 'm_perusahaan.lokasi');
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('t_lowongan_pekerjaan.nama_lowongan', 'LIKE', "%{$search}%")
                            ->orWhere('m_perusahaan.nama', 'LIKE', "%{$search}%");
                    });
                }
                if ($degree !== 'All Degree' && in_array($degree, $degreeOptions)) {
                    $query->whereJsonContains('t_lowongan_pekerjaan.kualifikasi_pendidikan', $degree);
                }
                $query->whereDate('t_lowongan_pekerjaan.batas_akhir', '>=', $toleranceDate);
                $contentData = $query->orderBy('t_lowongan_pekerjaan.created_at', 'desc')->paginate(9)->withQueryString();
                break;
        }

        return Inertia::render('app/landingpage/landingpage-page', [
            'auth' => $authData,
            'banners' => $banners,
            'degreeOptions' => $degreeOptions,
            'state' => ['activeTab' => $activeTab, 'search' => $search, 'degree' => $degree],
            'contentData' => $contentData,
        ]);
    }

    public function perusahaan(Request $request)
    {
        $authData = $this->getAuthData($request);
        $search = $request->query('search', '');

        // Buat dua variabel tanggal
        $toleranceDate = Carbon::now()->subDays(3); // Tidak dipakai di query ini, tapi konsisten
        $activeDate = Carbon::today(); // [BARU] Hanya hitung yang aktif mulai hari ini

        $query = PerusahaanModel::query();
        if ($search) {
            $query->where('nama', 'LIKE', "%{$search}%");
        }

        $companies = $query->orderBy('nama', 'asc')->paginate(10)->withQueryString();

        // Gunakan $activeDate untuk menghitung total_jobs
        $companies->getCollection()->transform(function ($company) use ($activeDate) {
            $countLowongan = LowonganPekerjaanModel::where('id_perusahaan', $company->id_perusahaan)
                ->whereDate('batas_akhir', '>=', $activeDate)->count(); // Hanya yang belum expired

            $countCampus = CampusHiringModel::where('id_perusahaan', $company->id_perusahaan)
                ->whereDate('batas_akhir', '>=', $activeDate)->count();

            $company->total_jobs = $countLowongan + $countCampus;

            return $company;
        });

        return Inertia::render('app/landingpage/landing-perusahaan-page', [
            'auth' => $authData,
            'state' => ['search' => $search],
            'contentData' => $companies,
        ]);
    }

    public function lowongan(Request $request)
    {
        $authData = $this->getAuthData($request);
        $search = $request->query('search', '');
        $degree = $request->query('degree', 'All Degree');
        $degreeOptions = ['D3', 'D4', 'S1', 'S2', 'S3'];
        $toleranceDate = Carbon::now()->subDays(3); // [KEMBALI KE ASAL]

        $query = LowonganPekerjaanModel::query();
        $query->join('m_perusahaan', 't_lowongan_pekerjaan.id_perusahaan', '=', 'm_perusahaan.id_perusahaan')
            ->select('t_lowongan_pekerjaan.*', 'm_perusahaan.nama as nama_perusahaan', 'm_perusahaan.url_logo', 'm_perusahaan.lokasi');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('t_lowongan_pekerjaan.nama_lowongan', 'LIKE', "%{$search}%")
                    ->orWhere('m_perusahaan.nama', 'LIKE', "%{$search}%");
            });
        }

        if ($degree !== 'All Degree' && in_array($degree, $degreeOptions)) {
            $query->whereJsonContains('t_lowongan_pekerjaan.kualifikasi_pendidikan', $degree);
        }

        $query->whereDate('t_lowongan_pekerjaan.batas_akhir', '>=', $toleranceDate);

        $contentData = $query->orderBy('t_lowongan_pekerjaan.created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('app/landingpage/landing-lowongan-page', [
            'auth' => $authData,
            'degreeOptions' => $degreeOptions,
            'state' => ['search' => $search, 'degree' => $degree],
            'contentData' => $contentData,
        ]);
    }

    public function campusHiring(Request $request)
    {
        $authData = $this->getAuthData($request);
        $search = $request->query('search', '');
        $degree = $request->query('degree', 'All Degree');
        $degreeOptions = ['D3', 'D4', 'S1', 'S2', 'S3'];
        $toleranceDate = Carbon::now()->subDays(3); // [KEMBALI KE ASAL]

        $query = CampusHiringModel::query();
        $query->join('m_perusahaan', 't_campus_hiring.id_perusahaan', '=', 'm_perusahaan.id_perusahaan')
            ->select('t_campus_hiring.*', 'm_perusahaan.nama as nama_perusahaan', 'm_perusahaan.url_logo', 'm_perusahaan.lokasi');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('t_campus_hiring.nama_campus_hiring', 'LIKE', "%{$search}%")
                    ->orWhere('m_perusahaan.nama', 'LIKE', "%{$search}%");
            });
        }

        if ($degree !== 'All Degree' && in_array($degree, $degreeOptions)) {
            $query->whereJsonContains('t_campus_hiring.kualifikasi_pendidikan', $degree);
        }

        $query->whereDate('t_campus_hiring.batas_akhir', '>=', $toleranceDate);

        $contentData = $query->orderBy('t_campus_hiring.created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('app/landingpage/landing-campus-hiring-page', [
            'auth' => $authData,
            'degreeOptions' => $degreeOptions,
            'state' => ['search' => $search, 'degree' => $degree],
            'contentData' => $contentData,
        ]);
    }

    public function storeLamaran(Request $request)
    {
        $auth = $request->attributes->get('auth');
        if (! $auth) {
            return back()->with('error', 'Anda harus login.');
        }

        $request->validate([
            'id_campus_hiring' => 'required|exists:t_campus_hiring,id_campus_hiring',
            'nama_pelamar' => 'required|string|max:255',
            'url_cv' => 'required|string|max:1000',
        ]);

        // [BARU] VALIDASI TANGGAL SERVER-SIDE
        // Ambil data campus hiring
        $campusHiring = CampusHiringModel::find($request->id_campus_hiring);

        // Cek apakah hari ini sudah melewati batas akhir
        if ($campusHiring && Carbon::today()->gt(Carbon::parse($campusHiring->batas_akhir))) {
            return back()->with('error', 'Pendaftaran untuk lowongan ini sudah ditutup.');
        }

        $exists = LamaranCampusHiringModel::where('id_campus_hiring', $request->id_campus_hiring)
            ->where('user_id', $auth->id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Anda sudah mendaftar di lowongan ini.');
        }

        LamaranCampusHiringModel::create([
            'id_campus_hiring' => $request->id_campus_hiring,
            'user_id' => $auth->id,
            'nama_pelamar' => $request->nama_pelamar,
            'url_cv' => $request->url_cv,
        ]);

        return back()->with('success', 'Lamaran berhasil dikirim!');
    }
}
