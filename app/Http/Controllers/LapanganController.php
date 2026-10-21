<?php

namespace App\Http\Controllers;

use App\Models\Lapangan;
use Illuminate\Http\Request;

class LapanganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lapangan = Lapangan::all();
        return view('lapangan.index', compact('lapangan'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    return view('lapangan.create');
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    Lapangan::create($request->all());

    return redirect('/lapangan')->with('success', 'Data berhasil ditambah');
    }


    /**
     * Display the specified resource.
     */
    public function show(Lapangan $lapangan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
    $lapangan = Lapangan::findOrFail($id);
    return view('lapangan.edit', compact('lapangan'));
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
    $lapangan = Lapangan::findOrFail($id);
    $lapangan->update($request->all());

    return redirect('/lapangan')->with('success', 'Data berhasil diupdate');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
    Lapangan::destroy($id);
    return redirect('/lapangan')->with('success', 'Data berhasil dihapus');
    }

}
