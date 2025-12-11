<?php

namespace App\Http\Controllers\App\Pengumuman;

use App\Http\Controllers\Controller;
use App\Models\PengumumanModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * @property int $id
 * @property string $judul
 * @property string $isi
 * @property string $expired_date
 * @property string|null $gambar_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PengumumanController extends Controller
{
    public function index(Request $request)
    {
        $query = PengumumanModel::query();

        if ($request->filled('search')) {
            $searchTerm = strtolower($request->search);

            // Menggunakan wrapping function($q) agar logika OR tidak merusak filter lain (jika ada)
            $query->where(function ($q) use ($searchTerm) {
                // Cari di Judul (huruf kecil vs huruf kecil)
                $q->whereRaw('LOWER(judul) LIKE ?', ["%{$searchTerm}%"])
                  // OPSIONAL: Cari juga di dalam Isi pengumuman agar hasil lebih banyak
                    ->orWhereRaw('LOWER(isi) LIKE ?', ["%{$searchTerm}%"]);
            });
        }

        // Urutkan dari yang terbaru
        $data = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('app/pengumuman/pengumuman-page', [
            'data' => $data,
            'filters' => $request->only(['search']),
        ]);
    }

    public function postChange(Request $request)
    {
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'isi' => 'required|string',
                'expired_date' => 'required|date|after_or_equal:today',
                'gambar' => 'nullable|mimes:jpg,jpeg,png|max:2048',
                'delete_gambar' => 'nullable|boolean', // <--- Tambahkan validasi ini
            ], [
                'expired_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh tanggal yang sudah lewat.',
            ]);

            $data = [
                'judul' => $request->judul,
                'isi' => $request->isi,
                'expired_date' => $request->expired_date,
            ];

            // Ambil data lama jika mode edit
            $oldData = null;
            if ($request->id) {
                $oldData = PengumumanModel::find($request->id);
            }

            // --- LOGIKA GAMBAR BARU ---
            if ($request->hasFile('gambar')) {
                // 1. Jika User Upload Gambar Baru
                // Hapus gambar lama jika ada
                if ($oldData && $oldData->gambar_path) {
                    Storage::disk('public')->delete($oldData->gambar_path);
                }
                // Simpan gambar baru
                $path = $request->file('gambar')->store('uploads/pengumuman', 'public');
                $data['gambar_path'] = $path;

            } elseif ($request->boolean('delete_gambar')) {
                // 2. Jika User Klik Hapus Gambar (Tanpa upload baru)
                if ($oldData && $oldData->gambar_path) {
                    Storage::disk('public')->delete($oldData->gambar_path);
                }
                // Set ke NULL di database
                $data['gambar_path'] = null;
            }

            PengumumanModel::updateOrCreate(['id' => $request->id], $data);

            return redirect()->back()->with('success', 'Data berhasil disimpan');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function postDelete(Request $request)
    {
        $item = PengumumanModel::find($request->id);
        if ($item) {
            if ($item->gambar_path) {
                Storage::disk('public')->delete($item->gambar_path);
            }
            $item->delete();
        }

        return redirect()->back();
    }
}
