<div class="border-end bg-white" id="sidebar-wrapper">
    <div class="sidebar-heading border-bottom bg-light">
        <i class="fas fa-graduation-cap me-2"></i>
        <span>Kurs Yönetim</span>
    </div>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('dashboard'); ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Ana Sayfa
        </a>

        <a href="ogrenciler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('ogrenciler'); ?>">
            <i class="fas fa-user-graduate me-2"></i> Öğrenciler
        </a>

        <a href="veliler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('veliler'); ?>">
            <i class="fas fa-users me-2"></i> Veliler
        </a>

        <a href="ogretmenler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('ogretmenler'); ?>">
            <i class="fas fa-chalkboard-teacher me-2"></i> Öğretmenler
        </a>

        <a href="dersler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('dersler'); ?>">
            <i class="fas fa-book me-2"></i> Dersler
        </a>

        <a href="siniflar.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('siniflar'); ?>">
            <i class="fas fa-door-open me-2"></i> Sınıflar
        </a>

        <a href="donemler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('donemler'); ?>">
            <i class="fas fa-calendar-alt me-2"></i> Dönemler
        </a>

        <a href="ders_programi.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('ders_programi'); ?>">
            <i class="fas fa-calendar-week me-2"></i> Ders Programı
        </a>

        <a href="yoklama_listesi.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('yoklama'); ?>">
            <i class="fas fa-clipboard-list me-2"></i> Yoklama
        </a>

        <a href="mufredat.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('mufredat'); ?>">
            <i class="fas fa-list-ol me-2"></i> Müfredat
        </a>

        <a href="odemeler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('odemeler'); ?>">
            <i class="fas fa-credit-card me-2"></i> Ödemeler
        </a>

        <a href="giderler.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('giderler'); ?>">
            <i class="fas fa-money-bill-wave me-2"></i> Giderler
        </a>

        <a href="raporlar.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('raporlar'); ?>">
            <i class="fas fa-chart-bar me-2"></i> Raporlar
        </a>

        <?php if (checkPermission(['Admin', 'Yönetici'])): ?>
            <a href="ayarlar.php" class="list-group-item list-group-item-action list-group-item-light p-3 <?php echo isActiveMenu('ayarlar'); ?>">
                <i class="fas fa-cog me-2"></i> Ayarlar
            </a>
        <?php endif; ?>
    </div>
</div>