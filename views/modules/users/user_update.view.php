<?php use App\Security\Guard; ?>
            <!-- Page header -->
            <div class="full-box page-header">
                <h3 class="text-left">
                    <i class="fas fa-sync-alt fa-fw"></i> &nbsp; ACTUALIZAR USUARIO
                </h3>
            </div>
            <?php $flash = Guard::flashGet(); ?>
            <?php if ($flash !== ''): ?>
                <div class="alert alert-warning" role="alert"><?php echo Guard::e($flash); ?></div>
            <?php endif; ?>

            <div class="container-fluid">
                <ul class="full-box list-unstyled page-nav-tabs">
                    <li>
                        <a href="?c=Users&a=userCreate"><i class="fas fa-plus fa-fw"></i> &nbsp; NUEVO USUARIO</a>
                    </li>
                    <li>
                        <a href="?c=Users&a=userRead"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; CONSULTAR USUARIOS</a>
                    </li>
                </ul>
            </div>

            <div class="container-fluid">
                <form action="?c=Users&a=userUpdate" method="POST" class="form-neon" autocomplete="off">
                    <?php echo Guard::csrfField(); ?>
                    <fieldset>
                        <legend><i class="far fa-address-card"></i> &nbsp; Actualizar Información personal</legend>
                        <div class="container-fluid">
                            <div class="row">
                                <input type="hidden" name="user_code" id="user_code" value="<?php echo Guard::e($user->getUserCode()); ?>">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="rol_code" class="bmd-label-floating">Rol</label>
                                        <select class="form-control" name="rol_code" id="rol_code">
                                            <?php $selected = ' selected'; ?>
                                            <?php foreach ($roles as $rol) : ?>
                                                <option value="<?php echo Guard::e($rol->getRolCode()); ?>"<?php echo $rol->getRolCode() === $user->getRolCode() ? $selected : ''; ?>><?php echo Guard::e($rol->getRolName()); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_state" class="bmd-label-floating">Estado</label>
                                        <select class="form-control" name="user_state" id="user_state">
                                            <option value="1"<?php echo $user->getUserState() === 1 ? $selected : ''; ?>>Activo</option>
                                            <option value="0"<?php echo $user->getUserState() === 0 ? $selected : ''; ?>>Inactivo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_name" class="bmd-label-floating">Nombres</label>
                                        <input type="text" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{1,35}" class="form-control" name="user_name" id="user_name" maxlength="35" value="<?php echo Guard::e($user->getUserName()); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_lastname" class="bmd-label-floating">Apellidos</label>
                                        <input type="text" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]{1,35}" class="form-control" name="user_lastname" id="user_lastname" maxlength="35" value="<?php echo Guard::e($user->getUserLastName()); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_id" class="bmd-label-floating">Identificación</label>
                                        <input type="text" pattern="[0-9()+]{5,20}" class="form-control" name="user_id" id="user_id" maxlength="20" value="<?php echo Guard::e($user->getUserId()); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_email" class="bmd-label-floating">Email</label>
                                        <input type="email" class="form-control" name="user_email" id="user_email" maxlength="100" value="<?php echo Guard::e($user->getUserEmail()); ?>" required>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_pass" class="bmd-label-floating">Contraseña nueva</label>
                                        <input type="password" class="form-control" name="user_pass" id="user_pass" maxlength="72" autocomplete="new-password">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="user_pass_conf" class="bmd-label-floating">Repetir contraseña</label>
                                        <input type="password" class="form-control" name="user_pass_conf" id="user_pass_conf" maxlength="72" autocomplete="new-password">
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
