<?php

namespace App\Imports;

use App\Models\Aluno;
use App\Models\Curso;
use App\Models\Avaliacao;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class AvaliacaoImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $row['data'] = implode('-',array_reverse(explode('-',$row['data'])));

        $curso = Curso::firstOrNew(['user_id' => auth()->user()->id,'curso'=>$row['curso']]);
        $curso->save();

        $aluno = Aluno::whereEstudante($row['estudante'])->whereHas('cursos', function (Builder $builder) use ($row) {
            $builder = $builder->whereCurso($row['curso'])
                            ->whereNull('data_avaliacao')
                            ->whereNull('avaliacao');
            if (!is_null($row['data']))
                $builder = $builder->where('inscricao','<=',$row['data'])
                                    ->where('ult_acesso','>=',$row['data']);
            return $builder;
        })->first();
        if (!$aluno)
            $aluno = Aluno::whereEstudante($row['estudante'])->whereHas('cursos', function (Builder $builder) use ($row) {
                return $builder->whereCurso($row['curso'])->where('diploma','>=',$row['data']);
            })->first();
        if (!$aluno) {
            $aluno = Aluno::whereEstudante($row['estudante'])->whereDoesntHave('cursos', function (Builder $builder) use ($row) {
                return $builder->whereCurso($row['curso']);
            })->first();
            if (!$aluno) $aluno = Aluno::create(['user_id' => auth()->user()->id,'estudante'=>$row['estudante']]);
            $curso = Curso::whereCurso($row['curso'])->first();
            if (!$curso) $curso = Curso::create(['user_id' => auth()->user()->id,'curso'=>$row['curso']]);
            $aluno->cursos()->attach($curso);
        }
        $aluno->cursos()->updateExistingPivot($curso->id,[
            'data_avaliacao' => $row['data'],
            'avaliacao' => $row['avaliacao'],
            'comentario' => $row['comentario'],
        ]);

        return new Avaliacao([
            'user_id' => auth()->user()->id,
            'curso' => $row['curso'],
            'estudante' => $row['estudante'],
            'data' => $row['data'],
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
