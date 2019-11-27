<?php

namespace App\Imports;

use App\Models\Aluno;
use App\Models\Curso;
use App\Models\Diploma;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class DiplomaImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $row['carga'] = $this->intCarga($row['carga']);
        $row['data'] = preg_match('/[\d]{2}\/[\d]{2}\/[\d]{4}/',$row['data']) ? implode('-',array_reverse(explode('/',$row['data']))) : null;

        $curso = Curso::firstOrNew(['user_id' => auth()->user()->id,'curso'=>$row['nome_evento']]);
        $curso->carga_horaria = $row['carga'];
        $curso->save();

        // Aluno cadastrado e relacionado com curso, sem diploma, com inscrição menor e ult_acesso maior ou igual
        $aluno = Aluno::whereEstudante($row['nome_usuario'])->whereHas('cursos', function (Builder $builder) use ($row) {
            $builder = $builder->whereCurso($row['nome_evento'])
                            ->whereNull('diploma');
            if (!is_null($row['data']))
                $builder = $builder->where('inscricao','<=',$row['data'])
                                    ->where('ult_acesso','>=',$row['data']);
            return $builder;
        })->first();
        if (!$aluno)
            $aluno = Aluno::whereEstudante($row['nome_usuario'])->whereHas('cursos', function (Builder $builder) use ($row) {
                return $builder->whereCurso($row['nome_evento'])->where('data_avaliacao','<=',$row['data']);
            })->first();
        if (!$aluno){
            $aluno = Aluno::whereEstudante($row['nome_usuario'])->whereDoesntHave('cursos', function (Builder $builder) use ($row) {
                return $builder->whereCurso($row['nome_evento']);
            })->first();
            if (!$aluno) $aluno = Aluno::create(['user_id' => auth()->user()->id,'estudante'=>$row['nome_usuario']]);
            $aluno->cursos()->attach($curso);
        }
        $aluno->cursos()->updateExistingPivot($curso->id,[
            'diploma' => $row['data'],
        ]);

        return new Diploma([
            'user_id' => auth()->user()->id,
            'curso' => $row['nome_evento'],
            'estudante' => $row['nome_usuario'],
            'carga' => $row['carga'],
            'data' => $row['data'],
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
