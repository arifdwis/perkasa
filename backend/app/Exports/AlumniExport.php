<?php

namespace App\Exports;

use App\Models\AlumniProfile;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AlumniExport implements FromView, ShouldAutoSize, WithHeadings
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = AlumniProfile::with('user');

        if (isset($this->filters['status_verifikasi'])) {
            $query->where('status_verifikasi', $this->filters['status_verifikasi']);
        }

        if (isset($this->filters['program_studi'])) {
            $query->where('program_studi', $this->filters['program_studi']);
        }

        if (isset($this->filters['tahun_masuk'])) {
            $query->where('tahun_masuk', $this->filters['tahun_masuk']);
        }

        if (isset($this->filters['tahun_lulus'])) {
            $query->where('tahun_lulus', $this->filters['tahun_lulus']);
        }

        $alumni = $query->orderBy('created_at', 'desc')->get();

        return view('exports.alumni', [
            'alumni' => $alumni,
        ]);
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'Email',
            'NIM',
            'Program Studi',
            'Tahun Masuk',
            'Tahun Lulus',
            'WhatsApp',
            'Domisili',
            'Status Verifikasi',
            'Terdaftar',
        ];
    }
}
