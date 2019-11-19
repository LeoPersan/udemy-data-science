<?php

namespace App\Http\Controllers;

use Lava;
use App\Models\Aluno;
use App\Models\Curso;
use App\Models\Diploma;
use App\Models\Instrutor;
use App\Models\Matricula;
use App\Models\Avaliacao;
use Illuminate\Http\Request;
use App\Imports\AlunoImport;
use App\Imports\DiplomaImport;
use App\Imports\AvaliacaoImport;
use App\Imports\MatriculaImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
    public function index()
    {
        $avaliacoesCursos = Avaliacao::withoutGlobalScope('order')->distinct()->get('curso')->map(function ($curso) {
            $curso->quantidades = $curso->medias = [];
            $curso->qtde_avaliacoes = $curso->sum_avaliacoes = 0;

            Avaliacao::whereCurso($curso->curso)->get()->map(function ($avaliacao) use (&$curso) {
                $quantidades = $curso->quantidades;
                $mes = date('Y-m',strtotime($avaliacao->data));

                if (!isset($quantidades[$mes])) {
                    $quantidades[$mes] = ['avaliacoes' => 0,'comentarios' => 0];
                }
                if (!is_null($avaliacao->comentario)) {
                    $curso->qtde_comentarios++;
                    $quantidades[$mes]['comentarios']++;
                }
                $curso->qtde_avaliacoes++;
                $quantidades[$mes]['avaliacoes']++;

                $curso->media += $avaliacao->avaliacao;
                $curso->quantidades = $quantidades;
            });
            
            $curso->media = $curso->media/$curso->qtde_avaliacoes;

            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Avaliações')
                    ->addNumberColumn('Comentários');
            foreach ($curso->quantidades as $mes => $quantidade) {
                $dataTable->addRow([$mes, $quantidade['avaliacoes'], $quantidade['comentarios']]);
            }
            Lava::AreaChart(str_slug($curso->curso).'QtdeAvaliacoes', $dataTable, [
                'title' => 'Qtde de Avaliações e Comentários',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
            ]);

            return $curso;
        })->sort(function ($a,$b) {
            return $a->media < $b->media;
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Médias');
        foreach ($avaliacoesCursos as $curso) {
            $dataTable->addRow([$curso->curso,$curso->media]);
        }
        Lava::ColumnChart('mediaAvaliacoes', $dataTable, [
            'title' => 'Média das Avaliações',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Avaliações')
                ->addNumberColumn('Comentários');
        foreach ($avaliacoesCursos as $curso) {
            $dataTable->addRow([$curso->curso,$curso->qtde_avaliacoes,$curso->qtde_comentarios]);
        }
        Lava::ColumnChart('qtdeAvaliacoes', $dataTable, [
            'title' => 'Quantidade de Avaliações e Comentários',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $matriculasCursos = Matricula::distinct()->get('curso')->map(function ($curso) {
            $curso->matriculas = Matricula::whereCurso($curso->curso)->get();
            return $curso;
        })->sort(function ($a,$b) {
            return $a->media_progresso < $b->media_progresso;
        });

        $diplomasCursos = Diploma::distinct()->get('curso')->map(function ($curso) {

            return $curso;
        });

        return view('home',[
            'avaliacoesCursos' => $avaliacoesCursos,
            'matriculasCursos' => $matriculasCursos,
            'diplomasCursos' => $diplomasCursos,
        ]);
    }

    public function upload(Request $request)
    {
        if ($request->has('arquivos')) {
            foreach ($request->file('arquivos') as $arquivo) {
                if (preg_match('/curso;estudante;data;avaliacao;comentario;/', $arquivo->get())) {
                    $arquivo->storeAs('public/avaliacoes', 'avaliacao-' . date('Ymd-His') . '-' . uniqid() . '.csv');
                    Excel::import(new AvaliacaoImport(), $arquivo);
                }
                if (preg_match('/[^\;]+;[^\;]+;[^\;]+;[^\;]+;[^\;]+;[^\;]+;[^\;]+;/', $arquivo->get())) {
                    $arquivo->storeAs('public/matriculas', 'matricula-' . date('Ymd-His') . '-' . uniqid() . '.csv');
                    Excel::import(new MatriculaImport(), $arquivo);
                }
                if (preg_match('/Nome_usuario,nome_evento,carga,data/', $arquivo->get())) {
                    $arquivo->storeAs('public/diplomas', 'diploma-' . date('Ymd-His') . '-' . uniqid() . '.csv');
                    Excel::import(new DiplomaImport(), $arquivo);
                }
                if (preg_match('/Nome,ano_nascimento,formacao/', $arquivo->get())) {
                    $arquivo->storeAs('public/alunos', 'aluno-' . date('Ymd-His') . '-' . uniqid() . '.csv');
                    Excel::import(new AlunoImport(), $arquivo);
                }
                $cursos = json_decode($arquivo->get());
                if ($cursos) {
                    foreach ($cursos->results as $result) {
                        $curso = Curso::whereCurso($result->title)->first() ?: new Curso([
                            'user_id' => auth()->user()->id,
                            'curso' => $result->title,
                            'preco' => $result->price_detail->amount,
                        ]);
                        $curso->preco = $result->price_detail->amount;
                        $curso->save();
                        foreach ($result->visible_instructors as $insctructor) {
                            $instrutor = Instrutor::whereInstrutor($insctructor->display_name)->first() ?: Instrutor::create([
                                'user_id' => auth()->user()->id,
                                'instrutor' => $insctructor->display_name,
                            ]);
                            $curso->instrutores()->attach($instrutor);
                        }
                    }
                }
            }
        }
        return redirect(route('home'));
    }
}
