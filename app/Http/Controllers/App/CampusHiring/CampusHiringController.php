<?php

namespace App\Http\Controllers\App\CampusHiring;

use App\Helper\ConstHelper;
use App\Helper\ToolsHelper;
use App\Http\Controllers\Controller;
use App\Models\CampusHiringModel;
use App\Models\LamaranCampusHiringModel;
use App\Models\PerusahaanModel; // Import Model Lamaran
use Illuminate\Http\Request; // Import Spreadsheet
use Illuminate\Support\Str; // Import Writer Excel
use Illuminate\Validation\Rule; // Import Styling Excel
use Inertia\Inertia; // Import Helper Str (PENTING untuk Str::slug)
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CampusHiringController extends Controller
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

        $campusHiringList = CampusHiringModel::query()
            ->where('id_admin_pembuat', $auth->id)
            ->when($search, function ($query) use ($search) {
                $lower = strtolower($search);
                $query->where(function ($q) use ($lower) {
                    $q->whereRaw('LOWER(nama_campus_hiring) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(deskripsi) LIKE ?', ["%{$lower}%"])
                        ->orWhereRaw('LOWER(departemen) LIKE ?', ["%{$lower}%"]);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return Inertia::render('app/campus-hiring/campus-hiring-page', [
            'campusHiringList' => fn () => $campusHiringList,
            'perusahaanList' => PerusahaanModel::all(),
            'jenisLowonganList' => CampusHiringModel::getJenisOptions(),
            'pageName' => Inertia::always('Daftar Campus Hiring'),
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
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah data Campus Hiring.');
        }

        $validJenis = CampusHiringModel::getJenisOptions();

        $request->validate([
            'id_perusahaan' => 'required|string',
            'jenis_lowongan' => ['required', 'string', Rule::in($validJenis)],
            'nama_campus_hiring' => 'required|string|max:255',
            'departemen' => 'nullable|string|max:100',
            'deskripsi' => 'nullable|string',
            'kualifikasi' => 'nullable|string',
            'benefit' => 'nullable|string',
            'kualifikasi_pendidikan' => 'nullable|array',
            'batas_akhir' => 'nullable|date',
        ]);

        // --- UPDATE MODE ---
        if (isset($request->id_campus_hiring) && ! empty($request->id_campus_hiring)) {
            $campusHiring = CampusHiringModel::where('id_campus_hiring', $request->id_campus_hiring)
                ->where('id_admin_pembuat', $auth->id)
                ->first();

            if (! $campusHiring) {
                return back()->with('error', 'Data tidak ditemukan atau Anda tidak memiliki akses.');
            }

            $campusHiring->id_perusahaan = $request->id_perusahaan;
            $campusHiring->jenis_lowongan = $request->jenis_lowongan;
            $campusHiring->nama_campus_hiring = $request->nama_campus_hiring;
            $campusHiring->departemen = $request->departemen;
            $campusHiring->deskripsi = $request->deskripsi;
            $campusHiring->kualifikasi = $request->kualifikasi;
            $campusHiring->benefit = $request->benefit;
            $campusHiring->kualifikasi_pendidikan = $request->kualifikasi_pendidikan;
            $campusHiring->batas_akhir = $request->batas_akhir;

            $campusHiring->save();

            return back()->with('success', 'Campus Hiring berhasil diperbarui.');
        }

        // --- CREATE MODE ---
        CampusHiringModel::create([
            'id_perusahaan' => $request->id_perusahaan,
            'id_admin_pembuat' => $auth->id,
            'jenis_lowongan' => $request->jenis_lowongan,
            'nama_campus_hiring' => $request->nama_campus_hiring,
            'departemen' => $request->departemen,
            'deskripsi' => $request->deskripsi,
            'kualifikasi' => $request->kualifikasi,
            'benefit' => $request->benefit,
            'kualifikasi_pendidikan' => $request->kualifikasi_pendidikan,
            'batas_akhir' => $request->batas_akhir,
        ]);

        return back()->with('success', 'Campus Hiring berhasil ditambahkan.');
    }

    public function postDelete(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $isEditor = $this->checkIsEditor($auth);

        if (! $isEditor) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengolah data Campus Hiring.');
        }

        $request->validate([
            'ids_campus_hiring' => 'required|array',
        ]);

        CampusHiringModel::whereIn('id_campus_hiring', $request->ids_campus_hiring)
            ->where('id_admin_pembuat', $auth->id)
            ->delete();

        return back()->with('success', 'Data Campus Hiring yang dipilih berhasil dihapus.');
    }

    /**
     * Download Excel Data Pelamar
     */
    public function downloadApplicants($id)
    {
        // Ambil data lowongan dulu
        $campusHiring = CampusHiringModel::find($id);

        // ✅ Guard: jika lowongan tidak ditemukan, jangan lanjut ke Excel / exit
        if (! $campusHiring) {
            return back()->with('error', 'Lowongan tidak ditemukan.');
        }

        $lamaran = LamaranCampusHiringModel::where('id_campus_hiring', $id)
            ->orderBy('tanggal_lamaran', 'desc')
            ->get();

        $namaJob = $campusHiring->nama_campus_hiring ?? 'Data';

        // Setup Spreadsheet
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Header Judul
        $sheet->setCellValue('A1', 'Daftar Pelamar - '.$namaJob);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Kolom
        $headers = ['No', 'Nama Pelamar', 'Link CV', 'Waktu Mendaftar'];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:D3')->getFont()->setBold(true);
        $sheet->getStyle('A3:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Isi Data
        $row = 4;
        foreach ($lamaran as $idx => $item) {
            $sheet->setCellValue('A'.$row, $idx + 1);
            $sheet->setCellValue('B'.$row, $item->nama_pelamar);
            $sheet->setCellValue('C'.$row, $item->url_cv);

            $waktu = $item->tanggal_lamaran
                ? date('d/m/Y H:i', strtotime($item->tanggal_lamaran))
                : '-';

            $sheet->setCellValue('D'.$row, $waktu);
            $sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Pelamar_'.Str::slug($namaJob).'_'.date('Y-m-d_His').'.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        // @codeCoverageIgnoreStart
        exit;
        // @codeCoverageIgnoreEnd

    }

    private function checkIsEditor($auth)
    {
        return ToolsHelper::checkRoles('Campus Hiring', $auth->akses);
    }
}
