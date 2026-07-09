<?php
	include_once('actions/ManterRelatorio.php');
	
	$manterRelatorio = new ManterRelatorio();
	
	$lista = $manterRelatorio->listarCaracterizacao();
        
        foreach ($lista as $obj) {
            echo "<tr>";
            echo "  <td>".$obj->id_submissao."</td>";
            echo "  <td>".$obj->uuid."</td>";
            echo "  <td>".$obj->codigo_selo."</td>";
            echo "  <td>".$obj->data_registro."</td>";
            $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-secondary btn-sm' type='button' title='Possuí dependências!'><i class='far fa-trash-alt' alt='Possuí dependências!'></i></button>";
            $btn_editar = "<a class='btn btn-primary btn-sm' type='button' href='editar_caracterizacao.php?selo=".$obj->codigo_selo."'><i class='fas fa-edit'></i></a>";           
            if($obj->excluir){
                $btn_excluir = "&nbsp;&nbsp;<button class='btn btn-danger btn-sm' type='button' onclick='excluir(".$obj->id_submissao.",\"".$obj->uuid."\")'><i class='far fa-trash-alt'></i></button>";
            }
            echo "  <td align='center'>".$btn_editar."&nbsp;&nbsp;".$btn_excluir."</td>";   
            echo "</tr>";
        }

