<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Tiga halaman HTML (dulu index.html, admin.html, superadmin.html).
 * Datanya tetap dimuat lewat JavaScript memanggil /api/*, seperti aplikasi lama.
 */
class PageController extends Controller
{
    public function index(): View
    {
        return view('index');
    }

    public function admin(): View
    {
        return view('admin');
    }

    public function superadmin(): View
    {
        return view('superadmin');
    }
}
