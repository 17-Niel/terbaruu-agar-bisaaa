<?php

namespace App\Http\Controllers\App\Artikel;

use App\Http\Controllers\Controller;
use App\Models\ArtikelModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArtikelController extends Controller
{
    /**
     * TAMPILKAN LIST ARTIKEL + SEARCH + PAGINATION
     */
    public function index(Request $request)
    {
        $search = $request->search ?? '';
        $perPage = 10;

        $query = ArtikelModel::query();

        // SEARCH
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%$search%")
                    ->orWhere('content', 'LIKE', "%$search%");
            });
        }

        // PAGINATION
        $artikelList = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->appends($request->only('search'));

        return Inertia::render('app/artikel/artikel-page', [
            'artikelList' => $artikelList,
            'search' => $search,
            'page' => $request->page ?? 1,
            'isEditor' => true,
            'perPage' => $perPage,
        ]);
    }

    /**
     * TAMPILKAN DETAIL ARTIKEL
     */
    // File: app/Http/Controllers/App/Artikel/ArtikelController.php

    public function show($id)
    {
        $artikel = ArtikelModel::findOrFail($id);

        // Konversi ke array dulu agar kita bisa menyisipkan data custom 'attachment_url'
        $artikelData = $artikel->toArray();

        if ($artikel->attachment) {
            $artikelData['attachment_url'] = asset('storage/'.$artikel->attachment);
        } else {
            $artikelData['attachment_url'] = null;
        }

        return Inertia::render('app/artikel/artikeldetailpage', [
            'artikel' => $artikelData, // Kirim array yang sudah dimodifikasi
        ]);
    }

    /**
     * TAMBAH / UBAH ARTIKEL
     */
    public function changePost(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|string',
            'title' => 'required|string',
            'content' => 'required|string',
            'category' => 'required|string',
            'attachment' => 'nullable|file',
        ]);

        $data = [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'],
        ];

        // Upload file jika ada
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('artikel_files', 'public');
            $data['attachment'] = $path;
        }

        // UPDATE
        if (! empty($validated['id'])) {
            ArtikelModel::where('id', $validated['id'])->update($data);
        }
        // CREATE
        else {
            ArtikelModel::create($data);
        }

        return redirect()->back()->with('success', 'Artikel berhasil disimpan.');
    }

    /**
     * HAPUS ARTIKEL
     */
    public function deletePost(Request $request)
    {
        $ids = $request->artikelIds ?? [];

        if (! empty($ids)) {
            ArtikelModel::whereIn('id', $ids)->delete();
        }

        return redirect()->back()->with('success', 'Artikel berhasil dihapus.');
    }
}
