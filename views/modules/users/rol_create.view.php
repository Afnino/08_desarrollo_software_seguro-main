<?php use App\Security\Guard; ?>
            <!-- Page header -->
            <div class="full-box page-header">
                <h3 class="text-left">
                    <i class="fas fa-plus fa-fw"></i> &nbsp; AGREGAR ROL
                </h3>
            </div>
            <?php $flash = Guard::flashGet(); ?>
            <?php if ($flash !== ''): ?>
                <div class="alert alert-warning" role="alert"><?php echo Guard::e($flash); ?></div>
            <?php endif; ?>

            <div class="container-fluid">
                <ul class="full-box list-unstyled page-nav-tabs">
                    <li>
                        <a class="active" href="?c=Users&a=rolCreate"><i class="fas fa-plus fa-fw"></i> &nbsp; AGREGAR ROL</a>
                    </li>
                    <li>
                        <a href="?c=Users&a=rolRead"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; CONSULTAR ROLES</a>
                    </li>
                </ul>
            </div>

            <div class="container-fluid">
                <form action="?c=Users&a=rolCreate" method="POST" class="form-neon" autocomplete="off">
                    <?php echo Guard::csrfField(); ?>
                    <fieldset>
                        <legend><i class="fas fa-user"></i> &nbsp; Agregar Rol</legend>
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="rol_name" class="bmd-label-floating">Nombre</label>
                                        <input type="text" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{2,40}" class="form-control" name="rol_name" id="rol_name" maxlength="40" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <p class="text-center" style="margin-top: 40px;">
                        <button type="reset" class="btn btn-raised btn-secondary btn-sm"><i class="fas fa-paint-roller"></i> &nbsp; LIMPIAR</button>
                        &nbsp; &nbsp;
                        <button type="submit" class="btn btn-raised btn-info btn-sm"><i class="far fa-save"></i> &nbsp; ENVIAR</button>
                    </p>
                </form>
            </div>
