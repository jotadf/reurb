<?php $tema = rtrim((string) cfg('tema_base'), '/') . '/'; ?>
            </div><!-- /.container-fluid -->
        </div><!-- /#content -->

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto"><span><?= cfg('rodape') ?></span></div>
            </div>
        </footer>
    </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->

<!-- Confirmação de exclusão (usada na listagem e na ficha) -->
<div class="modal fade" id="modalExcluir" tabindex="-1" role="dialog" aria-labelledby="modalExcluirTitulo" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" method="post" action="excluir.php">
            <?= csrf_campo() ?>
            <input type="hidden" name="t" value="">
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExcluirTitulo">Excluir registro</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                Confirma a exclusão de <strong data-alvo="descricao"></strong>?<br>
                <small class="text-muted">A exclusão fica registrada na auditoria.</small>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" type="submit"><i class="fas fa-trash-alt"></i> Excluir</button>
            </div>
        </form>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top" aria-label="Voltar ao topo"><i class="fas fa-angle-up"></i></a>

<script src="<?= e($tema) ?>vendor/jquery/jquery.min.js"></script>
<script src="<?= e($tema) ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= e($tema) ?>vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="<?= e($tema) ?>js/sb-admin-2.min.js"></script>
<script src="assets/js/reurb-crud.js"></script>
</body>
</html>
