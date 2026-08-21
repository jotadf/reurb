<?php
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Painel de Acompanhamento — Selagem / REURB</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/painel.css">
</head>
<body>
<nav class="navbar navbar-dark bg-primary shadow-sm">
  <div class="container-fluid"><span class="navbar-brand mb-0 h1">Painel de Acompanhamento — Selagem / REURB</span><span class="text-white small" id="atualizado"></span></div>
</nav>
<div class="container-fluid py-4">
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <div class="form-row align-items-end">
        <div class="col-md-3"><label>Data inicial</label><input type="date" id="from" class="form-control"></div>
        <div class="col-md-3"><label>Data final</label><input type="date" id="to" class="form-control"></div>
        <div class="col-md-2"><button id="filtrar" class="btn btn-primary btn-block">Atualizar</button></div>
        <div class="col-md-2"><button id="limpar" class="btn btn-outline-secondary btn-block">Limpar filtros</button></div>
      </div>
    </div>
  </div>

  <div id="erro" class="alert alert-danger d-none"></div>
  <div id="loading" class="alert alert-info">Carregando indicadores...</div>

  <div class="row" id="cards">
    <div class="col-sm-6 col-xl-3 mb-3"><div class="card kpi shadow-sm"><div class="card-body"><div class="text-muted">Lotes selados</div><div class="kpi-value" id="kpi-lotes">0</div></div></div></div>
    <div class="col-sm-6 col-xl-3 mb-3"><div class="card kpi shadow-sm"><div class="card-body"><div class="text-muted">Domicílios</div><div class="kpi-value" id="kpi-domicilios">0</div></div></div></div>
    <div class="col-sm-6 col-xl-3 mb-3"><div class="card kpi shadow-sm"><div class="card-body"><div class="text-muted">Cadastros sociojurídicos</div><div class="kpi-value" id="kpi-socio">0</div></div></div></div>
    <div class="col-sm-6 col-xl-3 mb-3"><div class="card kpi shadow-sm"><div class="card-body"><div class="text-muted">Caracterizações de vulnerabilidade</div><div class="kpi-value" id="kpi-vul">0</div></div></div></div>
  </div>

  <div class="row">
    <div class="col-lg-7 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Conciliação pelo código do selo</div><div class="card-body"><canvas id="chartMatching"></canvas><p class="small text-muted mt-3 mb-0">O vínculo é verificado comparando <code>domicilios_import.numero_selo</code> com <code>cadastro_sociojuridico_import.codigo_selo</code>, após remoção de espaços e normalização para maiúsculas.</p></div></div></div>
    <div class="col-lg-5 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Situação dos cadastros</div><div class="card-body"><canvas id="chartPie"></canvas></div></div></div>
  </div>
  <div class="row">
    <div class="col-lg-8 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Evolução mensal — lotes x sociojurídicos</div><div class="card-body"><canvas id="chartMes"></canvas></div></div></div>
    <div class="col-lg-4 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Tipo de ocupação do imóvel</div><div class="card-body"><canvas id="chartOcupacao"></canvas></div></div></div>
  </div>
  <div class="row">
    <div class="col-lg-6 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Localização dos domicílios</div><div class="card-body"><canvas id="chartLocalizacao"></canvas></div></div></div>
    <div class="col-lg-6 mb-4"><div class="card shadow-sm h-100"><div class="card-header">Indicadores de vulnerabilidade</div><div class="card-body" id="vul-container"></div></div></div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="assets/js/painel.js"></script>
</body></html>
