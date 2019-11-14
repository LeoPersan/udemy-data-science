<?php

namespace App\Http\Controllers;

use App\Imports\AlunoImport;
use App\Imports\AvaliacaoImport;
use App\Imports\DiplomaImport;
use App\Imports\MatriculaImport;
use App\Models\Curso;
use App\Models\Instrutor;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HomeController extends Controller
{
    public function index()
    {
        return view('home');
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
