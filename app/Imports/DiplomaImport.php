<?php

namespace App\Imports;

use App\Models\Curso;
use App\Models\Diploma;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DiplomaImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $curso = Curso::firstOrNew(['user_id' => auth()->user()->id,'curso'=>$row['nome_evento']]);
        $curso->carga_horaria = $this->intCarga($row['carga']);
        $curso->save();
        return new Diploma([
            'user_id' => auth()->user()->id,
            'curso' => $row['nome_evento'],
            'estudante' => $row['nome_usuario'],
            'carga' => $this->intCarga($row['carga']),
            'data' => preg_match('/[\d]{2}\/[\d]{2}\/[\d]{4}/',$row['data']) ? implode('-',array_reverse(explode('/',$row['data']))) : null,
        ]);
    }

    public function intCarga($carga)
    {
        if (preg_match('/MIN$/',$carga))
            return (int) $carga;
        elseif (preg_match('/H$/',$carga))
            return ((int) $carga)*60;
    }
    
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'ISO-8859-1',
            'delimiter' => ',',
        ];
    }
}
