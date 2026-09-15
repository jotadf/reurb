<?php
	include_once('actions/ManterRelatorio.php');
	
	$manterRelatorio = new ManterRelatorio();
	
	$lista = $manterRelatorio->listarSociojuridico();
        
        foreach ($lista as $obj) {
            $class_existe = " text-success ";
            $texto_existe = " Domicílio existente ";
            if(!$manterRelatorio->existeDomicilioPorSelo($obj->codigo_selo)){
                $class_existe = " text-danger ";
                $texto_existe = " Não existe vínculo com Domicílio ";
            } 
            echo "<tr>";
            echo "  <td>".$obj->id_submissao."</td>";
            echo "  <td class='".$class_existe."' title='".$texto_existe."'>".$obj->codigo_selo."</td>";
            echo "  <td>".$obj->r1_nome."</td>";
            echo "  <td>".$obj->data_registro."</td>";
            $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-secondary btn-sm' type='button' title='Possuí dependências!'><i class='far fa-trash-alt' alt='Possuí dependências!'></i></button>";
            $btn_editar = "<a class='btn btn-primary btn-sm' type='button' href='editar_sociojuridico.php?selo=".$obj->codigo_selo."'><i class='fas fa-edit'></i></a>";
            //$btn_domicilios = "<a class='btn btn-info btn-sm' type='button' href='editar_domicilio.php?id_selagem=".$obj->id_submissao."'><i class='fas fa-home'></i></a>";
            $btn_domicilios = "";
            $btn_editar_selo = "&nbsp;&nbsp;<button class='btn btn-warning btn-sm' type='button' onclick='alterarSelo(".$obj->id_submissao.",\"".$obj->codigo_selo."\")'><i class='fa fa-random'></i></button>";

            if($obj->excluir){
                $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-danger btn-sm' type='button' onclick='excluir(".$obj->codigo_selo.",\"".$obj->r1_nome."\")'><i class='far fa-trash-alt'></i></button>";
            }
            echo "  <td align='center'>".$btn_editar."&nbsp;&nbsp;".$btn_editar_selo."&nbsp;&nbsp;".$btn_excluir."&nbsp;&nbsp;".$btn_domicilios."</td>";   
            echo "</tr>";
        }

