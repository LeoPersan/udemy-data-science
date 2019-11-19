@extends('layouts.app')
@push('scripts')
@columnchart('mediaAvaliacoes', 'mediaAvaliacoes')
@columnchart('qtdeAvaliacoes', 'qtdeAvaliacoes')
@foreach ($avaliacoesCursos as $curso)
@areachart(str_slug($curso->curso).'QtdeAvaliacoes', str_slug($curso->curso).'QtdeAvaliacoes')
@combochart(str_slug($curso->curso).'MediaAvaliacoes', str_slug($curso->curso).'MediaAvaliacoes')
@endforeach
@columnchart('totalPerguntas','totalPerguntas')
@columnchart('mediaProgresso','mediaProgresso')
@foreach ($matriculasCursos as $curso)
@combochart(str_slug($curso->curso).'QtdePerguntas', str_slug($curso->curso).'QtdePerguntas')
@combochart(str_slug($curso->curso).'MediaProgresso', str_slug($curso->curso).'MediaProgresso')
@endforeach
@columnchart('statusAlunos','statusAlunos')
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
                                    <div id="mediaAvaliacoes"></div>
                                    <div id="qtdeAvaliacoes"></div>
                                </div>
                            </div>
                        </div>
                        @foreach ($avaliacoesCursos as $curso)
                        <div class="card">
                            <div class="card-header" role="tab" id="{{str_slug($curso->curso)}}Header">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#avaliacoes"
                                        href="#{{str_slug($curso->curso)}}Content" aria-expanded="false"
                                        aria-controls="{{str_slug($curso->curso)}}Content">
                                        {{$curso->curso}}
                                    </a>
                                </h5>
                            </div>
                            <div id="{{str_slug($curso->curso)}}Content" class="collapse show" role="tabpanel"
                                aria-labelledby="{{str_slug($curso->curso)}}Header">
                                <div class="card-body">
                                    <div id="{{str_slug($curso->curso)}}QtdeAvaliacoes"></div>
                                    <div id="{{str_slug($curso->curso)}}MediaAvaliacoes"></div>
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
                                    <div id="totalPerguntas"></div>
                                </div>
                            </div>
                        </div>
                        @foreach ($matriculasCursos as $curso)
                        <div class="card">
                            <div class="card-header" role="tab" id="{{str_slug($curso->curso)}}Header">
                                <h5 class="mb-0">
                                    <a data-toggle="collapse" data-parent="#matriculas"
                                        href="#{{str_slug($curso->curso)}}Content" aria-expanded="false"
                                        aria-controls="{{str_slug($curso->curso)}}Content">
                                        {{$curso->curso}}
                                    </a>
                                </h5>
                            </div>
                            <div id="{{str_slug($curso->curso)}}Content" class="collapse show" role="tabpanel"
                                aria-labelledby="{{str_slug($curso->curso)}}Header">
                                <div class="card-body">
                                    <div id="{{str_slug($curso->curso)}}QtdePerguntas"></div>
                                    <div id="{{str_slug($curso->curso)}}MediaProgresso"></div>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection