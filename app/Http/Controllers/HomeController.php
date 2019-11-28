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
        $cursos = Curso::all();

        $cursosAvaliacoes = $cursos->filter(function ($curso) {
            return $curso->avaliacoes->count();
        })->sort(function ($a,$b) {
            return $a->media < $b->media;
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Avaliações')
                ->addNumberColumn('Comentários');
        foreach ($cursosAvaliacoes as $curso) {
            $dataTable->addRow([$curso->curso,$curso->avaliacoes->count(),$curso->comentarios->count()]);
        }
        Lava::ColumnChart('qtdeAvaliacoes', $dataTable, [
            'title' => 'Quantidade de Avaliações e Comentários',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Médias');
        foreach ($cursosAvaliacoes as $curso) {
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
                ->addNumberColumn('Médias');
        foreach ($cursosAvaliacoes as $curso) {
            $dataTable->addRow([$curso->curso,$curso->mediaPonderada]);
        }
        Lava::ColumnChart('mediaAvaliacoesPonderadas', $dataTable, [
            'title' => 'Média das Avaliações Ponderada',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        foreach ($cursosAvaliacoes as $curso) {
            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Avaliações')
                    ->addNumberColumn('Comentários');
            $dataTable2 = Lava::DataTable();
            $dataTable2->addStringColumn('Mês')
                    ->addNumberColumn('Média Acumulada')
                    ->addNumberColumn('Média Mensal')
                    ->addNumberColumn('Desvio Min.')
                    ->addNumberColumn('Desvio Max.');
            $avaliacoesAcumuladas = [];
            $dataTable3 = Lava::DataTable();
            $dataTable3->addStringColumn('Mês')
                    ->addNumberColumn('Média Acumulada')
                    ->addNumberColumn('Média Mensal')
                    ->addNumberColumn('Desvio Min.')
                    ->addNumberColumn('Desvio Max.');
            $avaliacoesAcumuladasPonderadas = [];
            foreach ($curso->avaliacoesMeses as $mes => $avaliacoes) {
                $dataTable->addRow([$mes, count($avaliacoes['avaliacoes']), count($avaliacoes['comentarios'])]);

                $avaliacoesAcumuladas = array_merge($avaliacoesAcumuladas, $avaliacoes['avaliacoes']);
                $media = array_sum($avaliacoesAcumuladas)/(count($avaliacoesAcumuladas)?:1);
                $desvios = [];
                foreach ($avaliacoesAcumuladas as $avaliacao) {
                    $desvio = $media-$avaliacao;
                    $desvios[] = $desvio < 0 ? -$desvio : $desvio;
                }
                $desvio_padrao = array_sum($desvios)/(count($desvios)?:1);
                $dataTable2->addRow([$mes, $media, array_sum($avaliacoes['avaliacoes'])/(count($avaliacoes['avaliacoes'])?:1), $media-$desvio_padrao, $media+$desvio_padrao]);

                $avaliacoesAcumuladasPonderadas = array_merge($avaliacoesAcumuladasPonderadas, $avaliacoes['avaliacoes']);
                $media = array_sum($avaliacoesAcumuladasPonderadas)/(count($avaliacoesAcumuladasPonderadas)?:1);
                $desvios = [];
                foreach ($avaliacoesAcumuladasPonderadas as $avaliacao) {
                    $desvio = $media-$avaliacao;
                    $desvios[] = $desvio < 0 ? -$desvio : $desvio;
                }
                $desvio_padrao = array_sum($desvios)/(count($desvios)?:1);
                $dataTable3->addRow([$mes, $media, array_sum($avaliacoes['avaliacoes'])/(count($avaliacoes['avaliacoes'])?:1), $media-$desvio_padrao, $media+$desvio_padrao]);
            }

            Lava::AreaChart($curso->slug.'QtdeAvaliacoes', $dataTable, [
                'title' => 'Qtde de Avaliações e Comentários',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
            ]);

            Lava::ComboChart($curso->slug.'MediaAvaliacoes', $dataTable2, [
                'title' => 'Média das Avaliações',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'bars'],
                    2 => ['type' => 'line'],
                    3 => ['type' => 'line']
                ]
            ]);

            Lava::ComboChart($curso->slug.'MediaAvaliacoesPonderadas', $dataTable3, [
                'title' => 'Média das Avaliações',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'bars'],
                    2 => ['type' => 'line'],
                    3 => ['type' => 'line']
                ]
            ]);
        }

        $cursosMatriculas = $cursos->filter(function ($curso) {
            return $curso->mediaProgresso;
        })->sort(function ($a,$b) {
            return $a->mediaProgresso < $b->mediaProgresso;
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Média de Progresso');
        foreach ($cursosMatriculas as $curso) {
            $dataTable->addRow([$curso->curso,$curso->mediaProgresso]);
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
                ->addNumberColumn('Horas');
        foreach ($cursosMatriculas as $curso) {
            $dataTable->addRow([$curso->curso,$curso->minutosAssistidas]);
        }
        Lava::ColumnChart('totalAssistido', $dataTable, [
            'title' => 'Total de Minutos Assistidos',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Perguntas');
        foreach ($cursosMatriculas as $curso) {
            $dataTable->addRow([$curso->curso,$curso->perguntas]);
        }
        Lava::ColumnChart('totalPerguntas', $dataTable, [
            'title' => 'Total de Perguntas',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        foreach ($cursosMatriculas as $curso) {
            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Perguntas Acumuladas')
                    ->addNumberColumn('Perguntas');
            $perguntasAcumulada = 0;
            foreach ($curso->perguntasMeses as $mes => $perguntas) {
                $perguntasAcumulada += $perguntas;
                $dataTable->addRow([$mes, $perguntasAcumulada, $perguntas]);
            }
            Lava::ComboChart($curso->slug.'QtdePerguntas', $dataTable, [
                'title' => 'Qtde de Perguntas',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'bars'],
                ]
            ]);

            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Progresso Acumulado')
                    ->addNumberColumn('Progresso');

            $dataTable2 = Lava::DataTable();
            $dataTable2->addStringColumn('Mês')
                    ->addNumberColumn('Minutos Assistidos Acumulado')
                    ->addNumberColumn('Minutos Assistidos');
            $progressoAcumulado = [];
            foreach ($curso->progressoMeses as $mes => $progresso) {
                $progressoAcumulado = array_merge($progressoAcumulado,$progresso);
                $somaProgresso = array_sum($progresso);
                $somaProgressoAcumulado = array_sum($progressoAcumulado);
                $dataTable->addRow([$mes, $somaProgressoAcumulado/(count($progressoAcumulado)?:1), $somaProgresso/(count($progresso)?:1)]);
                $dataTable2->addRow([$mes, $curso->carga_horaria/100*$somaProgressoAcumulado, $curso->carga_horaria/100*$somaProgresso]);
            }
            Lava::ComboChart($curso->slug.'MediaProgresso', $dataTable, [
                'title' => 'Média de Progresso %',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'bars'],
                ]
            ]);

            Lava::ComboChart($curso->slug.'TotalAssistido', $dataTable2, [
                'title' => 'Minutos Assistidos',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
                'series' => [
                    0 => ['type' => 'area'],
                    1 => ['type' => 'bars'],
                ]
            ]);
        }

        $cursosDiplomas = $cursos->filter(function ($curso) {
            return $curso->diplomados->count()
                    or $curso->desistentes->count()
                    or $curso->incompletos->count()
                    or $curso->completos->count();
        })->sort(function ($a,$b) {
            $sort = $a->diplomados->count()-$b->diplomados->count();
            if ($sort != 0) return $sort<0;
            $sort = $a->completos->count()-$b->completos->count();
            if ($sort != 0) return $sort<0;
            $sort = $a->incompletos->count()-$b->incompletos->count();
            if ($sort != 0) return $sort<0;
            return $a->desistentes->count()<$b->desistentes->count();
        });

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Cursos')
                ->addNumberColumn('Desistiram')
                ->addNumberColumn('Não Concluido')
                ->addNumberColumn('Concluido')
                ->addNumberColumn('Diploma');
        foreach ($cursosDiplomas as $curso) {
            $dataTable->addRow([$curso->curso,$curso->desistentes->count(),$curso->incompletos->count(),$curso->completos->count(),$curso->diplomados->count()]);
        }
        Lava::ColumnChart('statusAlunos', $dataTable, [
            'title' => 'Quantidade de alunos por status',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        foreach ($cursosDiplomas as $curso) {
            $dataTable = Lava::DataTable();
            $dataTable->addStringColumn('Mês')
                    ->addNumberColumn('Desistiram')
                    ->addNumberColumn('Não Concluido')
                    ->addNumberColumn('Concluido')
                    ->addNumberColumn('Diploma');
            foreach ($curso->meses as $mes => $alunos) {
                $dataTable->addRow([$mes,$alunos['desistentes'],$alunos['incompletos'],$alunos['completos'],$alunos['diplomados']]);
            }
            Lava::AreaChart($curso->slug.'StatusAlunos', $dataTable, [
                'title' => 'Quantidade de alunos por status',
                'titleTextStyle' => [
                    'color'    => '#eb6b2c',
                    'fontSize' => 14
                ],
            ]);
        }

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

        $sexos = [
            'Masculino' => Aluno::whereSexo('M')->count(),
            'Feminino' => Aluno::whereSexo('F')->count()
        ];
        arsort($sexos);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Sexo')
                ->addNumberColumn('Alunos');
        foreach ($sexos as $sexo => $alunos) {
            $dataTable->addRow([$sexo,$alunos]);
        }
        Lava::PieChart('sexoAlunos', $dataTable, [
            'title' => 'Quantidade de alunos por sexo',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        list($min,$max) = [floor(Curso::min('preco')),ceil(Curso::max('preco'))];
        $precos = [];
        foreach (range($min,$max) as $preco) {
            $precos[$preco] = Curso::where('preco','like',$preco.'%')->count();
        }
        $tamanho = floor(count($precos)/5);

        $maiorsoma = 0;
        $melhorpreco = 0;
        foreach (array_keys($precos) as $preco) {
            $soma = 0;
            for ($i=$preco; $i < $preco+$tamanho; $i++) { 
                if (!isset($precos[$i])) break;
                $soma += $precos[$i];
            }
            if ($soma > $maiorsoma) {
                $maiorsoma = $soma;
                $melhorpreco = $preco;
            }
        }

        $preco = array_keys($precos)[0];
        $i = 0;
        while ($preco < $melhorpreco-($tamanho*$i)) $i++;
        $preco = $melhorpreco-($tamanho*$i);

        $partes = [];
        $tamanho--;
        while (max(array_keys($precos)) > $preco) {
            for ($i=0; $i <= $tamanho; $i++) {
                if ($i == 0) {
                    $caro = $preco+$tamanho > $max ? $max : $preco+$tamanho;
                    $barato = $preco < $min ? $min : $preco;
                    $parte = $barato.'-'.$caro;
                    $partes[$parte] = 0;
                }
                $partes[$parte] += isset($precos[$preco]) ? $precos[$preco] : 0;
                $preco++;
            }
        }
        arsort($partes);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Preço')
                ->addNumberColumn('Cursos');
        foreach ($partes as $parte => $qtde) {
            $dataTable->addRow([$parte,$qtde]);
        }
        Lava::PieChart('precosCursos', $dataTable, [
            'title' => 'Quantidade de Cursos por preço',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        $instrutores = Instrutor::with('cursos')->get();

        $data = [];
        foreach ($instrutores as $instrutor) {
            $data[$instrutor->cursos->count()] = isset($data[$instrutor->cursos->count()]) ? $data[$instrutor->cursos->count()]+1 : 0;
        }
        arsort($data);

        $dataTable = Lava::DataTable();
        $dataTable->addStringColumn('Quantidade de Cursos')
                ->addNumberColumn('Quantidade de Instrutores');
        foreach ($data as $cursos => $instrutores) {
            $dataTable->addRow([$cursos,$instrutores]);
        }
        Lava::PieChart('instrutorCursos', $dataTable, [
            'title' => 'Quantidade de Instrutores para cada Quantidade de Cursos',
            'titleTextStyle' => [
                'color'    => '#eb6b2c',
                'fontSize' => 14
            ],
        ]);

        return view('home',[
            'cursosAvaliacoes' => $cursosAvaliacoes,
            'cursosMatriculas' => $cursosMatriculas,
            'cursosDiplomas' => $cursosDiplomas,
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
                if (preg_match('/[^\;]+;Student;Da-ed-Star;it-Vi-Last;Progress%;Questions Asked;Questions Answered;/', $arquivo->get())) {
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
