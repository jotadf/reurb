<?php
	include_once('actions/ManterRelatorio.php');
	
	$manterRelatorio = new ManterRelatorio();
	
	$lista = $manterRelatorio->listarSelagem();
        
        foreach ($lista as $obj) {
            echo "<tr>";
            echo "  <td>".$obj->id_submissao."</td>";
            echo "  <td>".$obj->uuid."</td>";
            echo "  <td>".$obj->data_formulario."</td>";
            $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-secondary btn-sm' type='button' title='Possuí dependências!'><i class='far fa-trash-alt' alt='Possuí dependências!'></i></button>";
            $btn_editar = "<a class='btn btn-primary btn-sm' type='button' href='editar_selagem.php?id=".$obj->id_submissao."'><i class='fas fa-edit'></i></a>";
            $btn_domicilios = "<button class='btn btn-danger btn-sm' type='button' title='Selegem sem domicílio'><i class='fas fa-home'></i></button>";
            if($manterRelatorio->getDomicilioPorSubmissaoPai($obj->id_submissao)->id_submissao_pai > 0){
                $btn_domicilios = "<a class='btn btn-info btn-sm' type='button' href='editar_domicilio.php?id_selagem=".$obj->id_submissao."'><i class='fas fa-home'></i></a>";
            }

            if($obj->excluir){
                $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-danger btn-sm' type='button' onclick='excluir(".$obj->id_submissao.",\"".$obj->uuid."\")'><i class='far fa-trash-alt'></i></button>";
            }
            echo "  <td align='center'>".$btn_editar."&nbsp;&nbsp;".$btn_domicilios."&nbsp;&nbsp;".$btn_excluir."</td>";   
            echo "</tr>";
        }

