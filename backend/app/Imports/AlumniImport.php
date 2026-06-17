<?php

namespace App\Imports;

use App\Models\ImportedAlumniRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AlumniImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * Map a row to a model.
     *
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Support duplicate rows skip in file or just insert
        return new ImportedAlumniRecord([
            'nim' => trim($row['nim']),
            'name' => trim($row['nama']),
            'program_studi' => trim($row['program_studi']),
            'tahun_masuk' => (int) $row['tahun_masuk'],
            'tahun_lulus' => (int) $row['tahun_lulus'],
            'email' => trim($row['email']),
            'whatsapp' => trim($row['whatsapp']),
        ]);
    }

    public function rules(): array
    {
        return [
            'nim' => ['required', 'max:50', 'unique:imported_alumni_records,nim'],
            'nama' => ['required', 'max:255'],
            'program_studi' => ['required', 'max:255'],
            'tahun_masuk' => ['required', 'integer'],
            'tahun_lulus' => ['required', 'integer'],
            'email' => ['required', 'email', 'unique:imported_alumni_records,email'],
            'whatsapp' => ['required', 'max:20'],
        ];
    }
}
