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
        <li class="menu-header">User Control</li>
        <li
            class="dropdown <?= in_array($current_page, ['admin.php', 'pelayan.php', 'driver.php', 'gudang.php', 'pelanggan.php']) ? 'active' : '' ?>">
            <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-user"></i>
                <span>User</span></a>
            <ul class="dropdown-menu">
                <li class="<?= $current_page == 'admin.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="admin.php">Admin</a></li>
                <li class="<?= $current_page == 'pelayan.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="pelayan.php">Pelayan</a></li>
                <li class="<?= $current_page == 'driver.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="driver.php">Driver</a></li>
                <li class="<?= $current_page == 'gudang.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="gudang.php">Gudang</a></li>
                <li class="<?= $current_page == 'pelanggan.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="pelanggan.php">Pelanggan</a></li>
            </ul>
        </li>
        <li class="menu-header">Barang Control</li>
        <li class="<?= $current_page == 'barang.php' ? 'active' : '' ?>">
            <a class="nav-link <?= $current_page == 'barang.php' ? 'active' : '' ?>" href="barang.php"><i
                    class="fas fa-fire"></i><span>Barang Data</span></a>
        </li>
        <li class="menu-header">Transaksi Control</li>
        <li
            class="dropdown <?= in_array($current_page, ['order.php', 'pengiriman.php', 'finish.php']) ? 'active' : '' ?>">
            <a href="#" class="nav-link has-dropdown"><i class="far fa-file-alt"></i>
                <span>Transaksi</span>
            </a>
            <ul class="dropdown-menu">
                <li class="<?= $current_page == 'order.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="order.php">Order Data</a></li>
                <li class="<?= $current_page == 'pengiriman.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="pengiriman.php">Pengiriman Data</a></li>
                <li class="<?= $current_page == 'finish.php' ? 'active' : '' ?>"><a class="nav-link"
                        href="finish.php">Finish Order Data</a></li>
            </ul>
        </li>
        <li class="menu-header">Laporan Control</li>
        <li
            class="dropdown <?= in_array($current_page, ['laporan-barang.php', 'laporan-transaksi.php']) ? 'active' : '' ?>">
            <a href="#" class="nav-link has-dropdown"><i class="fas fa-print"></i>
                <span>Laporan</span>
            </a>
            <ul class="dropdown-menu">
                <li class="<?= $current_page == 'laporan-barang.php' ? 'active' : '' ?>"><a
                        href="laporan-barang.php">Laporan Stok Barang</a></li>
                <li class="<?= $current_page == 'laporan-transaksi.php' ? 'active' : '' ?>"><a
                        href="laporan-transaksi.php">Laporan Transaksi</a></li>
            </ul>
        </li>
    </ul>
</aside>