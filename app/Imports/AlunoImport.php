<?php

namespace App\Imports;

use App\Models\Aluno;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AlunoImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return Aluno::firstOrNew([
            'user_id' => auth()->user()->id,
            'estudante' => $row['nome'],
            'ano' => $row['ano_nascimento'],
            'formacao' => $row['formacao'],
        ]);
    }
    
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'ISO-8859-1',
            'delimiter' => ',',
        ];
    }
}
