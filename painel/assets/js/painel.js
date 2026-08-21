(function($){
  'use strict';
  var charts = {};
  function fmt(n){ return Number(n||0).toLocaleString('pt-BR'); }
  function destroy(name){ if(charts[name]){charts[name].destroy(); delete charts[name];} }
  function makeBar(id, labels, values, title, horizontal){
    destroy(id); charts[id]=new Chart(document.getElementById(id),{type:'bar',data:{labels:labels,datasets:[{label:title,data:values,borderWidth:1}]},options:{responsive:true,maintainAspectRatio:false,indexAxis:horizontal?'y':'x',plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
  }
  function load(){
    $('#loading').removeClass('d-none'); $('#erro').addClass('d-none');
    var params={}; if($('#from').val())params.from=$('#from').val(); if($('#to').val())params.to=$('#to').val();
    $.getJSON('api/dashboard.php',params).done(function(r){
      if(!r.ok) throw new Error(r.erro||'Erro');
      var i=r.indicadores;
      $('#kpi-lotes').text(fmt(i.lotes)); $('#kpi-domicilios').text(fmt(i.domicilios)); $('#kpi-socio').text(fmt(i.sociojuridicos)); $('#kpi-vul').text(fmt(i.vulnerabilidade)); $('#atualizado').text('Atualizado em '+r.gerado_em);
      makeBar('chartMatching',r.matching.map(x=>x.categoria),r.matching.map(x=>x.total),'Quantidade',true);
      destroy('chartPie'); charts.chartPie=new Chart(document.getElementById('chartPie'),{type:'doughnut',data:{labels:['Domicílios com sociojurídico','Domicílios sem sociojurídico'],datasets:[{data:[i.domicilios_com_socio,i.domicilios_sem_socio]}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
      var months={}; r.meses_lotes.forEach(x=>months[x.mes]={lotes:+x.total,socio:0}); r.meses_socio.forEach(x=>{if(!months[x.mes])months[x.mes]={lotes:0,socio:0};months[x.mes].socio=+x.total;}); var labels=Object.keys(months).sort();
      destroy('chartMes'); charts.chartMes=new Chart(document.getElementById('chartMes'),{type:'line',data:{labels:labels,datasets:[{label:'Lotes',data:labels.map(m=>months[m].lotes),tension:.25},{label:'Sociojurídicos',data:labels.map(m=>months[m].socio),tension:.25}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
      makeBar('chartOcupacao',r.ocupacao.map(x=>x.categoria),r.ocupacao.map(x=>+x.total),'Domicílios',true);
      makeBar('chartLocalizacao',r.localizacao.map(x=>x.categoria),r.localizacao.map(x=>+x.total),'Domicílios',false);
      var html=''; $.each(r.vulnerabilidades,function(k,v){ html+='<div class="vul-item"><div class="vul-title">'+esc(v.label)+'</div>'; v.rows.forEach(function(row){html+='<div class="vul-row"><span>'+esc(row.categoria)+'</span><strong>'+fmt(row.total)+'</strong></div><div class="progress mb-2"><div class="progress-bar" role="progressbar" style="width:'+pct(row.total, r.indicadores.vulnerabilidade)+'%"></div></div>';}); html+='</div>'; }); $('#vul-container').html(html);
    }).fail(function(xhr){$('#erro').removeClass('d-none').text('Não foi possível carregar os dados. Verifique a conexão com o banco e as credenciais em config.php. '+(xhr.responseJSON&&xhr.responseJSON.erro?xhr.responseJSON.erro:''));}).always(function(){$('#loading').addClass('d-none');});
  }
  function pct(a,b){return b?Math.min(100,(a/b)*100):0} function esc(s){return $('<div>').text(s==null?'':s).html();}
  $('#filtrar').on('click',load); $('#limpar').on('click',function(){$('#from,#to').val('');load();}); $(load);
})(jQuery);
