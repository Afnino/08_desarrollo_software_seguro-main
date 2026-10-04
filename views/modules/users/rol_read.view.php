<?php
use App\Security\Guard;
use App\Security\InputValidator;
?>
            <!-- Page header -->
            <div class="full-box page-header">
                <h3 class="text-left">
                    <i class="fas fa-clipboard-list fa-fw"></i> &nbsp; LISTA DE ROLES
                </h3>
            </div>
            <?php $flash = Guard::flashGet(); ?>
            <?php if ($flash !== ''): ?>
                <div class="alert alert-warning" role="alert"><?php echo Guard::e($flash); ?></div>
            <?php endif; ?>

            <div class="container-fluid">
                <ul class="full-box list-unstyled page-nav-tabs">
                    <li>
                        <a href="?c=Users&a=rolCreate"><i class="fas fa-plus fa-fw"></i> &nbsp; AGREGAR ROL</a>
                    </li>
                    <li>
                        <a class="active" href="?c=Users&a=rolRead"><i class="fas fa-clipboard-list fa-fw"></i> &nbsp; CONSULTAR ROLES</a>
                    </li>
                </ul>
            </div>

            <div class="container-fluid">
                <div class="table-responsive">
                    <table class="table table-dark table-sm">
                        <thead>
                            <tr class="text-center roboto-medium">
                                <th>Código</th>
                                <th>NOMBRE</th>
                                <th>ACTUALIZAR</th>
                                <th>ELIMINAR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $rol) : ?>
                                <?php $protected = InputValidator::isProtectedRole($rol->getRolName()); ?>
                                <tr class="text-center">
                                    <td><?php echo Guard::e($rol->getRolCode()); ?></td>
                                    <td><?php echo Guard::e($rol->getRolName()); ?></td>
                                    <td>
                                        <?php if (!$protected): ?>
                                            <a href="?c=Users&a=rolUpdate&idRol=<?php echo Guard::e($rol->getRolCode()); ?>" class="btn btn-success" aria-label="Actualizar rol">
                                                <i class="fas fa-sync-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$protected): ?>
                                            <form method="post" action="?c=Users&a=rolDelete" class="d-inline">
                                                <?php echo Guard::csrfField(); ?>
                                                <input type="hidden" name="idRol" value="<?php echo Guard::e($rol->getRolCode()); ?>">
                                                <button type="submit" class="btn btn-warning" aria-label="Eliminar rol">
                                                    <i class="far fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
