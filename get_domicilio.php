<?php
	include_once('actions/ManterRelatorio.php');
	
	$manterRelatorio = new ManterRelatorio();
	
	$lista = $manterRelatorio->listarDomicilios();
        
        foreach ($lista as $obj) {
            echo "<tr>";
            echo "  <td>".$obj->numero_selo."</td>";
            echo "  <td>".$obj->nome_entrevistado."</td>";
            echo "  <td>".$obj->id_submissao_pai."</td>";
            echo "  <td>".$obj->data_formulario."</td>";
            $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-secondary btn-sm' type='button' title='Possuí dependências!'><i class='far fa-trash-alt' alt='Possuí dependências!'></i></button>";
            $btn_editar = "<a class='btn btn-primary btn-sm' type='button' href='editar_domicilio.php?id_selagem=".$obj->id_submissao_pai."'><i class='fas fa-edit'></i></a>";
            $btn_sociojuridico = "<button class='btn btn-danger btn-sm' type='button' title='Não possui cadastro de sócio jurídico'><i class='fa fa-balance-scale'></i></button>";
            if($manterRelatorio->getSociojuridicoPorCodigoSelo($obj->numero_selo)->id_submissao){
                $btn_sociojuridico = "<a class='btn btn-info btn-sm' type='button' href='editar_sociojuridico.php?selo=".$obj->numero_selo."'><i class='fa fa-balance-scale'></i></a>";
            }
            
            $btn_domicilios = "";
            if($obj->excluir){
                $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-danger btn-sm' type='button' onclick='excluir(".$obj->numero_selo.",\"".$obj->uuid."\")'><i class='far fa-trash-alt'></i></button>";
            }
            echo "  <td align='center'>".$btn_editar."&nbsp;&nbsp;".$btn_sociojuridico."&nbsp;&nbsp;".$btn_excluir."</td>";   
            echo "</tr>";
        }

