@extends('layouts.app')
@push('scripts')
@columnchart('qtdeAvaliacoes', 'qtdeAvaliacoes')
@columnchart('mediaAvaliacoes', 'mediaAvaliacoes')
@columnchart('mediaAvaliacoesPonderadas', 'mediaAvaliacoesPonderadas')
@foreach ($cursosAvaliacoes as $curso)
@areachart($curso->slug.'QtdeAvaliacoes', $curso->slug.'QtdeAvaliacoes')
@combochart($curso->slug.'MediaAvaliacoes', $curso->slug.'MediaAvaliacoes')
@combochart($curso->slug.'MediaAvaliacoesPonderadas', $curso->slug.'MediaAvaliacoesPonderadas')
@endforeach
@columnchart('mediaProgresso','mediaProgresso')
@columnchart('totalAssistido','totalAssistido')
@columnchart('totalPerguntas','totalPerguntas')
@foreach ($cursosMatriculas as $curso)
@combochart($curso->slug.'MediaProgresso', $curso->slug.'MediaProgresso')
@combochart($curso->slug.'TotalAssistido', $curso->slug.'TotalAssistido')
@combochart($curso->slug.'QtdePerguntas', $curso->slug.'QtdePerguntas')
@endforeach
@columnchart('statusAlunos','statusAlunos')
@foreach ($cursosDiplomas as $curso)
@areachart($curso->slug.'StatusAlunos', $curso->slug.'StatusAlunos')
@endforeach
@piechart('formacaoAlunos','formacaoAlunos')
@piechart('anosAlunos','anosAlunos')
@piechart('sexoAlunos','sexoAlunos')
@piechart('precosCursos','precosCursos')
@piechart('instrutorCursos','instrutorCursos')
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Dashboard</div>

                <div class="card-body">
                    @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                    @endif

                    <form method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Envie todos os arquivos</label>
                            <input type="file" class="form-control-file" name="arquivos[]" placeholder="Arquivos"
                                multiple accept=".csv,.xls,.xlsx,.json">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Enviar</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Avaliações</div>
                <div class="card-body">
                    <div id="avaliacoes" role="tablist" aria-multiselectable="true">
                        <div class="card">
                            <div class="card-header" role="tab" id="geralHeader">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#avaliacoes" href="#geralContent"
                                        aria-expanded="true" aria-controls="geralContent">
                                        Geral
                                    </a>
                                </h5>
                            </div>
                            <div id="geralContent" class="collapse show" role="tabpanel" aria-labelledby="geralHeader">
                                <div class="card-body">
                                    <div id="qtdeAvaliacoes"></div>
                                    <div id="mediaAvaliacoes"></div>
                                    <div id="mediaAvaliacoesPonderadas"></div>
                                </div>
                            </div>
                        </div>
                        @foreach ($cursosAvaliacoes as $curso)
                        <div class="card">
                            <div class="card-header" role="tab" id="{{$curso->slug}}Header">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#avaliacoes"
                                        href="#{{$curso->slug}}Content" aria-expanded="false"
                                        aria-controls="{{$curso->slug}}Content">
                                        {{$curso->curso}}
                                    </a>
                                </h5>
                            </div>
                            <div id="{{$curso->slug}}Content" class="collapse show" role="tabpanel"
                                aria-labelledby="{{$curso->slug}}Header">
                                <div class="card-body">
                                    <div id="{{$curso->slug}}QtdeAvaliacoes"></div>
                                    <div id="{{$curso->slug}}MediaAvaliacoes"></div>
                                    <div id="{{$curso->slug}}MediaAvaliacoesPonderadas"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Matrículas</div>
                <div class="card-body">
                    <div id="matriculas" role="tablist" aria-multiselectable="true">
                        <div class="card">
                            <div class="card-header" role="tab" id="geralHeader">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#matriculas" href="#geralContent"
                                        aria-expanded="true" aria-controls="geralContent">
                                        Geral
                                    </a>
                                </h5>
                            </div>
                            <div id="geralContent" class="collapse show" role="tabpanel" aria-labelledby="geralHeader">
                                <div class="card-body">
                                    <div id="mediaProgresso"></div>
                                    <div id="totalAssistido"></div>
                                    <div id="totalPerguntas"></div>
                                </div>
                            </div>
                        </div>
                        @foreach ($cursosMatriculas as $curso)
                        <div class="card">
                            <div class="card-header" role="tab" id="{{$curso->slug}}Header">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#matriculas"
                                        href="#{{$curso->slug}}Content" aria-expanded="false"
                                        aria-controls="{{$curso->slug}}Content">
                                        {{$curso->curso}}
                                    </a>
                                </h5>
                            </div>
                            <div id="{{$curso->slug}}Content" class="collapse show" role="tabpanel"
                                aria-labelledby="{{$curso->slug}}Header">
                                <div class="card-body">
                                    <div id="{{$curso->slug}}MediaProgresso"></div>
                                    <div id="{{$curso->slug}}TotalAssistido"></div>
                                    <div id="{{$curso->slug}}QtdePerguntas"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Diplomas</div>
                <div class="card-body">
                    <div id="diplomas" role="tablist" aria-multiselectable="true">
                        <div class="card">
                            <div class="card-header" role="tab" id="geralHeader">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#diplomas" href="#geralContent"
                                        aria-expanded="true" aria-controls="geralContent">
                                        Geral
                                    </a>
                                </h5>
                            </div>
                            <div id="geralContent" class="collapse show" role="tabpanel" aria-labelledby="geralHeader">
                                <div class="card-body">
                                    <div id="statusAlunos"></div>
                                </div>
                            </div>
                        </div>
                        @foreach ($cursosDiplomas as $curso)
                        <div class="card">
                            <div class="card-header" role="tab" id="{{$curso->slug}}Header">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#diplomas"
                                        href="#{{$curso->slug}}Content" aria-expanded="false"
                                        aria-controls="{{$curso->slug}}Content">
                                        {{$curso->curso}}
                                    </a>
                                </h5>
                            </div>
                            <div id="{{$curso->slug}}Content" class="collapse show" role="tabpanel"
                                aria-labelledby="{{$curso->slug}}Header">
                                <div class="card-body">
                                    <div id="{{$curso->slug}}StatusAlunos"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Alunos</div>
                <div class="card-body">
                    <div id="alunos" role="tablist" aria-multiselectable="true">
                        <div class="card">
                            <div class="card-header" role="tab" id="geralHeader">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#alunos" href="#geralContent"
                                        aria-expanded="true" aria-controls="geralContent">
                                        Geral
                                    </a>
                                </h5>
                            </div>
                            <div id="geralContent" class="collapse show" role="tabpanel" aria-labelledby="geralHeader">
                                <div class="card-body">
                                    <div id="formacaoAlunos"></div>
                                    <div id="anosAlunos"></div>
                                    <div id="sexoAlunos"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Cursos</div>
                <div class="card-body">
                    <div id="cursos" role="tablist" aria-multiselectable="true">
                        <div class="card">
                            <div class="card-header" role="tab" id="geralHeader">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#cursos" href="#geralContent"
                                        aria-expanded="true" aria-controls="geralContent">
                                        Geral
                                    </a>
                                </h5>
                            </div>
                            <div id="geralContent" class="collapse show" role="tabpanel" aria-labelledby="geralHeader">
                                <div class="card-body">
                                    <div id="precosCursos"></div>
                                    <div id="instrutorCursos"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection