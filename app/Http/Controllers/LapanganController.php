<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LapanganController extends Controller
{
    public function index()
    {
        $lapangan = Lapangan::all();
        return view('lapangan.index', compact('lapangan'));
    }

    public function create()
    {
        return view('lapangan.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_gallery' => 'nullable|array',
            'foto_gallery.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = $request->except(['foto', 'foto_gallery']);

        // Upload foto utama
        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')
                ->store('lapangan', 'public');
        }

        // Upload foto galeri
        $gallery = [];

        if ($request->hasFile('foto_gallery')) {
            foreach ($request->file('foto_gallery') as $file) {
                $gallery[] = $file->store('lapangan', 'public');
            }
        }

        $data['foto_gallery'] = $gallery;

        Lapangan::create($data);

        return redirect('/lapangan')
            ->with('success', 'Data berhasil ditambah');
    }

    public function show(Lapangan $lapangan)
    {
        //
    }

    public function edit($id)
    {
        $lapangan = Lapangan::findOrFail($id);

        return view('lapangan.edit', compact('lapangan'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'foto_gallery' => 'nullable|array',
            'foto_gallery.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $lapangan = Lapangan::findOrFail($id);

        $data = $request->except(['foto', 'foto_gallery']);

        // Ganti foto utama jika ada foto baru
        if ($request->hasFile('foto')) {
            if ($lapangan->foto) {
                Storage::disk('public')->delete($lapangan->foto);
            }

            $data['foto'] = $request->file('foto')
                ->store('lapangan', 'public');
        }

        // Ganti galeri jika ada foto galeri baru
        if ($request->hasFile('foto_gallery')) {
            $oldGallery = $lapangan->foto_gallery ?? [];

            if (is_string($oldGallery)) {
                $oldGallery = json_decode($oldGallery, true) ?? [];
            }

            foreach ($oldGallery as $oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }

            $gallery = [];

            foreach ($request->file('foto_gallery') as $file) {
                $gallery[] = $file->store('lapangan', 'public');
            }

            $data['foto_gallery'] = $gallery;
        }

        $lapangan->update($data);

        return redirect('/lapangan')
            ->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        $lapangan = Lapangan::findOrFail($id);

        if ($lapangan->foto) {
            Storage::disk('public')->delete($lapangan->foto);
        }

        $gallery = $lapangan->foto_gallery ?? [];

        if (is_string($gallery)) {
            $gallery = json_decode($gallery, true) ?? [];
        }

        foreach ($gallery as $photo) {
            Storage::disk('public')->delete($photo);
        }

        $lapangan->delete();

        return redirect('/lapangan')
            ->with('success', 'Data berhasil dihapus');
    }
}
