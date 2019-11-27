<?php

namespace App\Imports;

use App\Models\Aluno;
use App\Models\Curso;
use App\Models\Matricula;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class MatriculaImport implements ToModel, WithCustomCsvSettings, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $row['da_ed_star'] = preg_match('/[\d]{2}-[\d]{2}-[\d]{4}/',$row['da_ed_star']) ? implode('-',array_reverse(explode('-',$row['da_ed_star']))) : null;
        $row['it_vi_last'] = preg_match('/[\d]{2}-[\d]{2}-[\d]{4}/',$row['it_vi_last']) ? implode('-',array_reverse(explode('-',$row['it_vi_last']))) : null;
        $row['progress'] = (float) $row['progress'];

        $curso = Curso::firstOrNew(['user_id' => auth()->user()->id,'curso'=>$row['super_academia_data_science']]);
        $curso->save();

        // Aluno cadastrado e relacionado com curso, sem inscricao, com diploma maior ou igual que inscricao e diploma menor ou igual que ult_acesso ou data_avaliacao
        $aluno = Aluno::whereEstudante($row['student'])->whereHas('cursos', function (Builder $builder) use ($row) {
            $builder = $builder->whereCurso($row['super_academia_data_science'])
                                ->whereNull('inscricao')
                                ->whereNull('ult_acesso')
                                ->whereNull('progresso')
                                ->whereNull('perguntas_feitas')
                                ->whereNull('perguntas_respondidas');
            if (!is_null($row['da_ed_star']))
                $builder = $builder->where(function (Builder $builder) use ($row) {
                    return $builder->orWhere('diploma','>=',$row['da_ed_star'])
                                    ->orWhere('data_avaliacao','>=',$row['da_ed_star']);
                });
            if (!is_null($row['it_vi_last']))
                $builder = $builder->where(function (Builder $builder) use ($row) {
                    return $builder->orWhere('diploma','<=',$row['it_vi_last'])
                                    ->orWhere('data_avaliacao','<=',$row['it_vi_last']);
                });
            return $builder;
        })->first();
        if (!$aluno){
            $aluno = Aluno::whereEstudante($row['student'])->whereDoesntHave('cursos', function (Builder $builder) use ($row) {
                return $builder->whereCurso($row['super_academia_data_science']);
            })->first();
            if (!$aluno) $aluno = Aluno::create(['user_id' => auth()->user()->id,'estudante'=>$row['student']]);
            $aluno->cursos()->attach($curso);
        }
        $aluno->cursos()->updateExistingPivot($curso->id,[
            'inscricao' => $row['da_ed_star'],
            'ult_acesso' => $row['it_vi_last'],
            'progresso' => $row['progress'],
            'perguntas_feitas' => $row['questions_asked'],
            'perguntas_respondidas' => $row['questions_answered'],
        ]);

        return new Matricula([
            'user_id' => auth()->user()->id,
            'curso' => $row['super_academia_data_science'],
            'estudante' => $row['student'],
            'inicio' => $row['da_ed_star'],
            'ult_acesso' => $row['it_vi_last'],
            'progresso' => $row['progress'],
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
