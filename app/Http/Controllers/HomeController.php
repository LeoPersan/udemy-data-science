<?php

namespace App\Http\Controllers;

use App\Imports\AlunoImport;
use App\Imports\AvaliacaoImport;
use App\Imports\DiplomaImport;
use App\Imports\MatriculaImport;
use App\Models\Aluno;
use App\Models\Avaliacao;
use App\Models\Curso;
use App\Models\Diploma;
use App\Models\Instrutor;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
    public function index()
    {
        return view('home',[
            'avaliacoesCursos' => Avaliacao::distinct('curso')->get()->map(function ($curso) {
                $curso->avaliacoes = Avaliacao::whereCurso($curso->curso)->get();
                return $curso;
            }),
            'matriculasCursos' => Matricula::distinct('curso')->get()->map(function ($curso) {
                $curso->matriculas = Matricula::whereCurso($curso->curso)->get();
                return $curso;
            }),
            'diplomasCursos' => Diploma::distinct('curso')->get()->map(function ($curso) {
                $curso->diplomas = Diploma::whereCurso($curso->curso)->get();
                return $curso;
            }),
            'alunos' => Aluno::all(),
            'cursos' => Curso::with('instrutores')->get(),
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
