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
            $curso->qtde_perguntas = $curso->qtde_progressos = [];
            $curso->matriculas->map(function ($matricula) use (&$curso) {
                $curso->media_progresso += $matricula->progresso;
                $perguntas = $matricula->perguntas_feitas+$matricula->perguntas_respondidas;
                $curso->total_perguntas += $perguntas;

                $qtde_perguntas = $curso->qtde_perguntas;
                $qtde_progressos = $curso->qtde_progressos;
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

                    $meses = array_keys($qtde_perguntas);
                    $progresso = $matricula->progresso;
                    while ($progresso > 0) {
                        $media_progresso = floor($progresso/(count($meses)?:1));
                        $progresso -= $media_progresso;
                        $mes = array_shift($meses);
                        $qtde_progressos[$mes][] = $media_progresso;
                    }
                    $curso->qtde_progressos = $qtde_progressos;
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

            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Progresso Acumulado')
                    ->addNumberColumn('Progresso');
            $progressoAcumulado = [];
            foreach ($curso->qtde_progressos as $mes => $progresso) {
                $progressoAcumulada = array_merge($progressoAcumulado,$progresso);
                $dataTable->addRow([$mes, array_sum($progressoAcumulada)/(count($progressoAcumulada)?:1), array_sum($progresso)/(count($progresso)?:1)]);
            }
            Lava::ComboChart(str_slug($curso->curso).'MediaProgresso', $dataTable, [
                'title' => 'Média de Progresso',
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
            $curso->diplomas = Diploma::whereCurso($curso->curso)->get();
            $curso->desistentes = $curso->incompletos = $curso->completos = $curso->diplomados = 0;

            $curso->meses = [];
            $curso->diplomas->map(function ($diploma) use (&$curso) {
                $meses = $curso->meses;
                $mes = date('Y-m',strtotime($diploma->data));
                if (!isset($meses[$mes])) {
                    $meses[$mes] = [
                        'desistentes' => 0,
                        'incompletos' => 0,
                        'completos' => 0,
                        'diplomados' => 0,
                    ];
                }
                $meses[$mes]['diplomados']++;
                $curso->meses = $meses;
                return $diploma;
            });

            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Desistiram')
                    ->addNumberColumn('Não Concluido')
                    ->addNumberColumn('Concluido')
                    ->addNumberColumn('Diploma');
            $desistentes = 0;
            $incompletos = 0;
            $completos = 0;
            $diplomados = 0;
            foreach ($curso->meses as $mes => $alunos) {
                $desistentes += $alunos['desistentes'];
                $incompletos += $alunos['incompletos'];
                $completos += $alunos['completos'];
                $diplomados += $alunos['diplomados'];
                $dataTable->addRow([$mes,$desistentes,$incompletos,$completos,$diplomados]);
            }
            Lava::AreaChart(str_slug($curso->curso).'StatusAlunos', $dataTable, [
                'title' => 'Quantidade de alunos por status',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
            ]);

            $curso->desistentes = $desistentes;
            $curso->incompletos = $incompletos;
            $curso->completos = $completos;
            $curso->diplomados = $diplomados;

            return $curso;
        })->sort(function ($a,$b){
            return $a->diplomados<$b->diplomados;
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Desistiram')
                ->addNumberColumn('Não Concluido')
                ->addNumberColumn('Concluido')
                ->addNumberColumn('Diploma');
        foreach ($diplomasCursos as $curso) {
            $dataTable->addRow([$curso->curso,$curso->desistentes,$curso->incompletos,$curso->completos,$curso->diplomados]);
        }
        Lava::ColumnChart('statusAlunos', $dataTable, [
            'title' => 'Quantidade de alunos por status',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $formacoes = Aluno::distinct()->get('formacao')->map(function ($formacao) {
            $formacao->alunos = Aluno::whereFormacao($formacao->formacao)->count();
            return $formacao;
        })->sort(function ($a,$b) {
            return $a->alunos < $b->alunos;
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Formação')
                ->addNumberColumn('Alunos');
        foreach ($formacoes as $formacao) {
            $dataTable->addRow([$formacao->formacao,$formacao->alunos]);
        }
        Lava::PieChart('formacaoAlunos', $dataTable, [
            'title' => 'Quantidade de alunos por formação',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        list($min,$max) = [Aluno::min('ano'),Aluno::max('ano')];
        $anos = [];
        foreach (range($min,$max) as $ano) {
            $anos[$ano] = Aluno::whereAno($ano)->count();
        }
        $tamanho = floor(count($anos)/2.8);

        $maiorsoma = 0;
        $melhorano = 0;
        foreach (array_keys($anos) as $ano) {
            $soma = 0;
            for ($i=$ano; $i < $ano+$tamanho; $i++) { 
                if (!isset($anos[$i])) break;
                $soma += $anos[$i];
            }
            if ($soma > $maiorsoma) {
                $maiorsoma = $soma;
                $melhorano = $ano;
            }
        }

        $ano = array_keys($anos)[0];
        $i = 0;
        while ($ano < $melhorano-($tamanho*$i)) $i++;
        $ano = $melhorano-($tamanho*$i);

        $partes = [];
        $tamanho--;
        while (max(array_keys($anos)) > $ano) {
            for ($i=0; $i <= $tamanho; $i++) {
                if ($i == 0) {
                    $novo = $ano+$tamanho > $max ? $max : $ano+$tamanho;
                    $velho = $ano < $min ? $min : $ano;
                    $parte = (date('Y')-$novo).'-'.(date('Y')-$velho);
                    $partes[$parte] = 0;
                }
                $partes[$parte] += isset($anos[$ano]) ? $anos[$ano] : 0;
                $ano++;
            }
        }
        arsort($partes);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Idade')
                ->addNumberColumn('Alunos');
        foreach ($partes as $parte => $qtde) {
            $dataTable->addRow([$parte,$qtde]);
        }
        Lava::PieChart('anosAlunos', $dataTable, [
            'title' => 'Quantidade de alunos por idade',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

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
