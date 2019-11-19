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
                $medias = $curso->medias;
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

                $medias[$mes][] = $avaliacao->avaliacao;

                $curso->media += $avaliacao->avaliacao;
                $curso->quantidades = $quantidades;
                $curso->medias = $medias;
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

            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Média Acumulada')
                    ->addNumberColumn('Média Mensal')
                    ->addNumberColumn('Desvio Min.')
                    ->addNumberColumn('Desvio Max.');
            $avaliacoes = [];
            foreach ($curso->medias as $mes => $av) {
                $avaliacoes = array_merge($avaliacoes, $av);
                $media = array_sum($avaliacoes)/(count($avaliacoes)?:1);
                $desvios = [];
                foreach ($avaliacoes as $avaliacao) {
                    $desvio = $media-$avaliacao;
                    $desvios[] = $desvio < 0 ? -$desvio : $desvio;
                }
                $desvio_padrao = array_sum($desvios)/(count($desvios)?:1);
                $dataTable->addRow([$mes, $media, array_sum($av)/(count($av)?:1), $media-$desvio_padrao, $media+$desvio_padrao]);
            }
            Lava::ComboChart(str_slug($curso->curso).'MediaAvaliacoes', $dataTable, [
                'title' => 'Média das Avaliações',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'columns'],
                    2 => ['type' => 'line'],
                    3 => ['type' => 'line']
                ]
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
            $curso->media_progresso = $curso->total_perguntas = 0;
            $curso->qtde_perguntas = [];
            $curso->matriculas->map(function ($matricula) use (&$curso) {
                $curso->media_progresso += $matricula->progresso;
                $perguntas = $matricula->perguntas_feitas+$matricula->perguntas_respondidas;
                $curso->total_perguntas += $perguntas;

                $qtde_perguntas = $curso->qtde_perguntas;
                if (!in_array(null,[$matricula->inicio,$matricula->ult_acesso])) {
                    $mes = date('Y-m-01',strtotime('-1 month',strtotime($matricula->inicio)));
                    $mes_fim = date('Y-m-01',strtotime($matricula->ult_acesso));
                    $meses = [];
                    do {
                        $mes = date('Y-m-01',strtotime('+1 month',strtotime($mes)));
                        $meses[] = date('Y-m',strtotime($mes));
                    } while ($mes != $mes_fim);
                    
                    while ($perguntas > 0) {
                        $media_perguntas = floor($perguntas/(count($meses)?:1));
                        $perguntas -= $media_perguntas;
                        $mes = array_shift($meses);
                        $qtde_perguntas[$mes] = isset($qtde_perguntas[$mes]) ? $qtde_perguntas[$mes]+$media_perguntas : $media_perguntas;
                    }
                    $curso->qtde_perguntas = $qtde_perguntas;
                }
                return $matricula;
            });
            $curso->media_progresso /= $curso->matriculas->count();

            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Perguntas Acumuladas')
                    ->addNumberColumn('Perguntas');
            $quantidadeAcumulada = 0;
            foreach ($curso->qtde_perguntas as $mes => $quantidade) {
                $quantidadeAcumulada += $quantidade;
                $dataTable->addRow([$mes, $quantidadeAcumulada, $quantidade]);
            }
            Lava::ComboChart(str_slug($curso->curso).'QtdePerguntas', $dataTable, [
                'title' => 'Qtde de Perguntas',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'columns'],
                ]
            ]);

            return $curso;
        })->sort(function ($a,$b) {
            return $a->media_progresso < $b->media_progresso;
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Média de Progresso');
        foreach ($matriculasCursos as $curso) {
            $dataTable->addRow([$curso->curso,$curso->media_progresso]);
        }
        Lava::ColumnChart('mediaProgresso', $dataTable, [
            'title' => 'Média de Progressos',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Perguntas');
        foreach ($matriculasCursos as $curso) {
            $dataTable->addRow([$curso->curso,$curso->total_perguntas]);
        }
        Lava::ColumnChart('totalPerguntas', $dataTable, [
            'title' => 'Total de Perguntas',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

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
