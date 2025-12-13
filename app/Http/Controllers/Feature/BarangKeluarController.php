<?php

namespace App\Http\Controllers\Feature;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BarangKeluarController extends Controller
{
    /**
     * Halaman utama - hanya menampilkan 4 card menu
     * Tidak ada tabel riwayat di sini
     */
    public function index()
    {
        return view('admin.barang-keluar.index');
    }
}
