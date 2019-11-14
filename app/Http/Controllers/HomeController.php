<?php

namespace App\Http\Controllers;

use App\Imports\AvaliacaoImport;
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
                $arquivo->storeAs('public/avaliacoes','avaliacao-'.date('Ymd-His').'-'.uniqid().'.csv');
                Excel::import(new MatriculaImport(),$arquivo);
            }
        }
    }
}
