<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
    <div class="container-fluid">
        <button class="btn btn-sm" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="d-none d-md-block">
            <span class="navbar-text">
                <i class="fas fa-calendar me-1"></i> <?php echo date('d.m.Y'); ?>
            </span>
        </div>

        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user-circle me-1"></i> <?php echo $_SESSION['full_name']; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <div class="dropdown-header text-center">
                        <small class="text-muted"><?php echo ucfirst($_SESSION['role']); ?></small>
                    </div>
                    <a class="dropdown-item" href="profil.php">
                        <i class="fas fa-user-cog me-2"></i> Profil
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Çıkış Yap
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>