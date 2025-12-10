<?php

namespace App\Http\Controllers\App\Berita;

use App\Http\Controllers\Controller;
use App\Models\BeritaModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $query = BeritaModel::query();

        // Filter Pencarian
        if ($request->search) {
            $query->where('judul', 'like', '%'.$request->search.'%')
                ->orWhere('isi', 'like', '%'.$request->search.'%');
        }

        // Filter Status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Urutkan berdasarkan tanggal terbaru
        $data = $query->orderBy('tanggal', 'desc')->paginate(10);

        // Hitung Statistik
        $stats = [
            'total' => BeritaModel::count(),
            'published' => BeritaModel::where('status', 'published')->count(),
            'draft' => BeritaModel::where('status', 'draft')->count(),
            'views' => 0,
        ];

        return Inertia::render('app/berita/berita-page', [
            'berita' => $data,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function postChange(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'status' => 'required|in:draft,published',
            'isi' => 'required|string',
            'tanggal' => 'nullable|date',
            'author' => 'nullable|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'judul' => $request->judul,
            'status' => $request->status,
            'isi' => $request->isi,
            'tanggal' => $request->tanggal ?? now(),
            'author' => $request->author ?? 'Admin',
        ];

        // LOGIKA UPLOAD GAMBAR
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika edit
            if ($request->id) {
                $oldData = BeritaModel::find($request->id);
                if ($oldData && $oldData->gambar) {
                    Storage::disk('public')->delete($oldData->gambar);
                }
            }
            $path = $request->file('gambar')->store('berita', 'public');
            $data['gambar'] = $path;
        } elseif ($request->delete_gambar) {
            if ($request->id) {
                $oldData = BeritaModel::find($request->id);
                if ($oldData && $oldData->gambar) {
                    Storage::disk('public')->delete($oldData->gambar);
                }
            }
            $data['gambar'] = null;
        }

        // LOGIKA SIMPAN KE DATABASE (Explicit Check)
        if ($request->filled('id')) {
            // Jika ada ID, lakukan UPDATE
            BeritaModel::where('id_berita', $request->id)->update($data);
        } else {
            // Jika tidak ada ID, lakukan CREATE
            BeritaModel::create($data);
        }

        return back()->with('message', 'Data berhasil disimpan');
    }

    public function postDelete(Request $request)
    {
        // Pastikan request ID ditangkap dengan benar
        $id = $request->id;
        $data = BeritaModel::find($id);

        if ($data) {
            if ($data->gambar) {
                Storage::disk('public')->delete($data->gambar);
            }
            $data->delete();
        }

        return back()->with('message', 'Data berhasil dihapus');
    }
}
