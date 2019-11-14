<?php

namespace App\Imports;

use App\Models\Matricula;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MatriculaImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Matricula([
            'user_id' => auth()->user()->id,
            'curso' => $row['super_academia_data_science'],
            'estudante' => $row['student'],
            'inicio' => preg_match('/[\d]{2}-[\d]{2}-[\d]{4}/',$row['da_ed_star']) ? implode('-',array_reverse(explode('-',$row['da_ed_star']))) : null,
            'ult_acesso' => preg_match('/[\d]{2}-[\d]{2}-[\d]{4}/',$row['it_vi_last']) ? implode('-',array_reverse(explode('-',$row['it_vi_last']))) : null,
            'progresso' => (float) $row['progress'],
            'perguntas_feitas' => $row['questions_asked'],
            'perguntas_respondidas' => $row['questions_answered']
        ]);
    }
    
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
        ];
    }
}
