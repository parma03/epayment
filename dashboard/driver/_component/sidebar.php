<?php
// Ambil nama file saat ini
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="sidebar-wrapper">
    <div class="sidebar-brand">
        <a href="index.html">Stisla</a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
        <a href="index.html">St</a>
    </div>
    <ul class="sidebar-menu">
        <li class="menu-header">Dashboard</li>
        <li class="<?= $current_page == 'index.php' ? 'active' : '' ?>">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php"><i
                    class="fas fa-fire"></i><span>Dashboard</span></a>
        </li>
        <li class="menu-header">Pengiriman Control</li>
        <li class="<?= $current_page == 'pengiriman.php' ? 'active' : '' ?>">
            <a class="nav-link <?= $current_page == 'pengiriman.php' ? 'active' : '' ?>" href="pengiriman.php"><i
                    class="far fa-file-alt"></i><span>Pengiriman Data</span></a>
        </li>
    </ul>
</aside>