<?php
// index.php - UI + membaca file queue/processed + menampilkan notifikasi
session_start();

// file-file yang digunakan
$queueFile = __DIR__ . '/queue.txt';
$processedFile = __DIR__ . '/processed.txt';
$inventoryFile = __DIR__ . '/inventory.json';
$stackFile = __DIR__ . '/stack.txt';

// membaca stack (LIFO)
$stackRaw = trim(file_get_contents($stackFile));
$stackItems = $stackRaw === "" ? [] : array_filter(array_map('json_decode', explode("\n", $stackRaw)));

// memastikan file-file ada
if (!file_exists($queueFile)) file_put_contents($queueFile, "");
if (!file_exists($processedFile)) file_put_contents($processedFile, "");
if (!file_exists($inventoryFile))
if (!file_exists($stackFile)) file_put_contents($stackFile, ""); {
    // inventory default jika belum ada file inventory.json
    $defaultInventory = [
        ["id"=>1,"name"=>"Kompor Portable Gas","price"=>50000,"stock"=>3],
        ["id"=>2,"name"=>"Wajan Besar (chafing)","price"=>30000,"stock"=>4],
        ["id"=>3,"name"=>"Rice Cooker 5L","price"=>18000,"stock"=>5],
        ["id"=>4,"name"=>"Blender","price"=>15000,"stock"=>6],
        ["id"=>5,"name"=>"Mixer","price"=>12000,"stock"=>2],
        ["id"=>6,"name"=>"Panci","price"=>8000,"stock"=>8],
    ];
    file_put_contents($inventoryFile, json_encode($defaultInventory, JSON_PRETTY_PRINT));
}

// membaca antrian (queue) dan data pesanan terproses
$queueRaw = trim(file_get_contents($queueFile));
$processedRaw = trim(file_get_contents($processedFile));

$queueItems = $queueRaw === "" ? [] : array_filter(array_map('json_decode', explode("\n", $queueRaw)));
$processedItems = $processedRaw === "" ? [] : array_filter(array_map('json_decode', explode("\n", $processedRaw)));

$inventory = json_decode(file_get_contents($inventoryFile), true);

// session untuk pesan notifikasi & error
$flash = $_SESSION['flash'] ?? null;
$errors = $_SESSION['errors'] ?? null;
unset($_SESSION['flash'], $_SESSION['errors']);
?>

<!-- MODUL 8: GUI PROGRAMMING-->
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Sistem Rental Peralatan Dapur</title>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="logo"></div>
        <div>
            <h1>KitchenRent - Kitchen Equipment Rental</h1>
            <div class="subtitle">Temukan peralatan dapur terbaik, lengkap dalam satu tempat.</div>
        </div>
    </div>
</header>

<main class="wrap">
    <?php if ($flash): ?>
        <div class="notice"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="grid">

        <!-- KIRI: FORM CUSTOMER -->
        <section class="card left">
            <h2>Pilih item & ajukan sewa (Customer)</h2>

            <?php if ($errors): ?>
                <div class="errors">
                    <?php foreach ($errors as $e): ?>
                        <div class="err"><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="process.php" method="POST" class="form">
                <label>Nama Pemesan</label>
                <input type="text" name="nama" placeholder="Nama lengkap" required>

                <label>No HP (opsional)</label>
                <input type="text" name="hp" placeholder="08xxxx">

                <label>Pilih Peralatan</label>
                <select name="alat" required>
                    <option value="">-- Pilih alat --</option>
                    <?php foreach ($inventory as $it): 
                        // label yang tampil di dropdown
                        $optLabel = htmlspecialchars($it['name'] . " — Rp" . number_format($it['price']) . " — Stok: " . $it['stock']);
                    ?>
                        <option value="<?= $it['id'] ?>"><?= $optLabel ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="two">
                    <div>
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" min="1" value="1" required>
                    </div>
                    <div>
                        <label>Durasi sewa (hari)</label>
                        <input type="number" name="durasi" min="1" value="1" required>
                    </div>
                </div>

                <button class="cta">Ajukan Sewa</button>
            </form>
        </section>

        <!-- PANEL KANAN -->
        <aside class="card right">
            <div class="right-head">
                <h3>Antrian Permintaan</h3>
                <form action="process.php" method="POST" style="display:inline">
                    <input type="hidden" name="action" value="clear_history">
                    <button class="btn-small">Bersihkan Riwayat</button>
                </form>
            </div>

            <!-- daftar permintaan dalam queue -->
            <?php foreach ($queueItems as $q): ?>
                <div class="item">
                    <div class="name"><?= htmlspecialchars($q->customer) ?></div>
                    <div class="meta">
                        Item ID: <?= htmlspecialchars($q->item_id) ?> |
                        Durasi: <?= htmlspecialchars($q->duration) ?> hari |
                        Status: <?= htmlspecialchars($q->status) ?>
                    </div>
                    <div class="time">Waktu request: <?= htmlspecialchars($q->time) ?></div>
                </div>
            <?php endforeach; ?>

            <h3>Inventory</h3>
            <div class="box inv">
                <?php foreach ($inventory as $it): ?>
                    <div class="inv-item">
                        <div class="inv-title"><?= htmlspecialchars($it['name']) ?></div>
                        <div class="inv-meta">ID: <?= $it['id'] ?> — Rp <?= number_format($it['price']) ?>/hari</div>
                        <div class="inv-stock">Stok: <?= $it['stock'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3>Pesanan Terproses</h3>
            <div class="box proc">
                <?php if (count($processedItems) === 0): ?>
                    <div class="empty">Belum ada pesanan diproses.</div>
                <?php else: ?>
                    <?php foreach ($processedItems as $p): ?>
                        <div class="item processed">
                            <div class="name">
                                <?= htmlspecialchars($p->customer) ?>
                                <span class="badge">processed</span>
                            </div>
                            <div class="meta">
                                Item ID: <?= htmlspecialchars($p->item_id) ?> |
                                Durasi: <?= htmlspecialchars($p->duration) ?> hari
                            </div>
                            <div class="time"><?= htmlspecialchars($p->time) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>

    <!-- Riwayat Approve (Stack - LIFO) -->
    <h3>Riwayat Approve</h3>
    <div class="box proc">
        <?php if (count($stackItems) === 0): ?>
            <div class="empty">Belum ada riwayat approve.</div>
        <?php else: ?>
            <?php 
            // LIFO → tampilkan dari paling akhir (dibalik)
            $stackItems = array_reverse($stackItems);
            ?>
            <?php foreach ($stackItems as $s): ?>
                <div class="item processed">
                    <div class="name">
                        <?= htmlspecialchars($s->customer) ?>
                        <span class="badge">LIFO</span>
                    </div>

                    <div class="meta">
                        Item ID: <?= htmlspecialchars($s->item_id) ?> |
                        Durasi: <?= htmlspecialchars($s->duration) ?> hari
                    </div>

                    <div class="time">
                        Approved pada: <?= htmlspecialchars($s->approved_time ?? '-') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<footer class="foot">
    <div class="credit">TA Pemrograman Dasar • Universitas Diponegoro</div>
</footer>
</body>
</html>
