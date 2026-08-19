<!-- Card Content - Collapse -->
    <div class="card-body">
        <form id="form_relatorio" action="relatorio_busca_periodo.php" method="post">
            <input type="hidden" name="usuario_perfil" id="usuario_perfil" value="<?php echo $usuario_logado->perfil?>">
            <div class="col border p-4">
                <fieldset class="form-group">
                    <div class="row row-cols-lg-auto g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="termino" class="col-form-label">Tabela</label>
                            <select class="form-control form-control-sm w-100" id="tipo" name="tipo">
                                <option value="selagemdomicilio">Selagem e Domicílio</option>
                                <option value="selagem">Selagem</option>
                                <option value="domicilio">Domicílio</option>
                                <option value="socio_juridico">Sócio Jurídico</option>
                                <option value="caracterizacao">Caracterização</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="inicio" class="form-label">Data início</strong></label>
                            <input type="date" class="form-control form-control-sm" id="inicio" name="inicio" required>
                        </div>
                        <div class="col-md-4">
                            <label for="termino" class="form-label">Data término</label>
                            <input type="date" class="form-control form-control-sm" id="termino" name="termino" required>
                        </div>
                    </div>
                    <br/>
                </fieldset>
            </div>

            <br />
            <div class="form-group row float-right">
                <button type="reset" class="btn btn-danger btn-sm" ><i class="fa fa-refresh"></i> Limpar </button>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <button type="submit" class="btn btn-primary btn-sm" ><i class="fa fa-file"></i>Gerar</button>
                &nbsp;&nbsp;&nbsp;
            </div>
        </form>
    </div>

