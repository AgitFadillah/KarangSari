<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = LogAktivitas::query()
            ->cari($request->query('q'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.log.index', [
            'activeAdmin' => 'admin.log',
            'logs' => $logs,
            'q' => (string) $request->query('q', ''),
        ]);
    }
}
