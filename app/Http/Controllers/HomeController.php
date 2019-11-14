<?php

namespace App\Http\Controllers;

use App\Imports\AlunoImport;
use App\Imports\AvaliacaoImport;
use App\Imports\DiplomaImport;
use App\Imports\MatriculaImport;
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
        foreach ($request->file('arquivos') as $arquivo) {
            if (preg_match('/curso;estudante;data;avaliacao;comentario;/',$arquivo->get())) {
                $arquivo->storeAs('public/avaliacoes','avaliacao-'.date('Ymd-His').'-'.uniqid().'.csv');
                Excel::import(new AvaliacaoImport(),$arquivo);
            }
            if (preg_match('/[^\;]+;[^\;]+;[^\;]+;[^\;]+;[^\;]+;[^\;]+;[^\;]+;/',$arquivo->get())) {
                $arquivo->storeAs('public/matriculas','matricula-'.date('Ymd-His').'-'.uniqid().'.csv');
                Excel::import(new MatriculaImport(),$arquivo);
            }
            if (preg_match('/Nome_usuario,nome_evento,carga,data/',$arquivo->get())) {
                $arquivo->storeAs('public/diplomas','diploma-'.date('Ymd-His').'-'.uniqid().'.csv');
                Excel::import(new DiplomaImport(),$arquivo);
            }
            if (preg_match('/Nome,ano_nascimento,formacao/',$arquivo->get())) {
                $arquivo->storeAs('public/alunos','aluno-'.date('Ymd-His').'-'.uniqid().'.csv');
                Excel::import(new AlunoImport(),$arquivo);
            }
        }
    }
}
