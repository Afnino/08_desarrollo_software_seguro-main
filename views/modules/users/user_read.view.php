<?php use App\Security\Guard; ?>
            <!-- Page header -->
            <div class="full-box page-header">
                <h3 class="text-left">
                    <i class="fas fa-clipboard-list fa-fw"></i> &nbsp; LISTA DE USUARIOS
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
                        <a class="active" href="?c=Users&a=userRead"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; CONSULTAR USUARIOS</a>
                    </li>
                </ul>
            </div>

            <div class="container-fluid">
                <div class="table-responsive">
                    <table class="table table-dark table-sm">
                        <thead>
                            <tr class="text-center roboto-medium">
                                <th>ROL</th>
                                <th>CÓDIGO</th>
                                <th>NOMBRES</th>
                                <th>APELLIDOS</th>
                                <th>IDENTIFICACIÓN</th>
                                <th>EMAIL</th>
                                <th>ESTADO</th>
                                <th>ACTUALIZAR</th>
                                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                    <th>ELIMINAR</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $state = [0 => 'Inactivo', 1 => 'Activo']; ?>
                            <?php foreach ($users as $user) : ?>
                                <tr class="text-center">
                                    <td><?php echo Guard::e($user->getRolName()); ?></td>
                                    <td><?php echo Guard::e($user->getUserCode()); ?></td>
                                    <td><?php echo Guard::e($user->getUserName()); ?></td>
                                    <td><?php echo Guard::e($user->getUserLastName()); ?></td>
                                    <td><?php echo Guard::e($user->getUserId()); ?></td>
                                    <td><?php echo Guard::e($user->getUserEmail()); ?></td>
                                    <td><?php echo Guard::e($state[$user->getUserState()] ?? 'Desconocido'); ?></td>
                                    <td>
                                        <a href="?c=Users&a=userUpdate&idUser=<?php echo Guard::e($user->getUserCode()); ?>" class="btn btn-success" aria-label="Actualizar usuario">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                    </td>
                                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                                        <td>
                                            <form method="post" action="?c=Users&a=userDelete" class="d-inline">
                                                <?php echo Guard::csrfField(); ?>
                                                <input type="hidden" name="idUser" value="<?php echo Guard::e($user->getUserCode()); ?>">
                                                <button type="submit" class="btn btn-warning" aria-label="Eliminar usuario">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
