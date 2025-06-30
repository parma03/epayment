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
        <li class="menu-header">Transaksi Control</li>
        <li
            class="dropdown <?= in_array($current_page, ['konfirmasi.php', 'pengiriman.php', 'histori.php', 'gudang.php', 'pelanggan.php']) ? 'active' : '' ?>">
            <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-shopping-cart"></i>
                <span>Transaksi</span></a>
            <ul class="dropdown-menu">
                <li class="<?= $current_page == 'konfirmasi.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="konfirmasi.php">Konfirmasi</a></li>
                <li class="<?= $current_page == 'pengiriman.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="pengiriman.php">Pengiriman</a></li>
                <li class="<?= $current_page == 'histori.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="histori.php">Histori</a></li>
            </ul>
        </li>
    </ul>
</aside>