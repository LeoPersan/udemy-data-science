<?php

namespace App\Imports;

use App\Models\Avaliacao;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AvaliacaoImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Avaliacao([
            'user_id' => auth()->user()->id,
            'curso' => $row['curso'],
            'estudante' => $row['estudante'],
            'data' => implode('-',array_reverse(explode('-',$row['data']))),
            'avaliacao' => $row['avaliacao'],
            'comentario' => $row['comentario'],
        ]);
    }
    
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
        ];
    }
}
