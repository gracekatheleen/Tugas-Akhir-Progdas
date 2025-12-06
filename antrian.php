<?php 
session_start();

$queueFile = __DIR__.'/queue.txt';
$processedFile = __DIR__.'/processed.txt';
$stackFile = __DIR__.'/stack.txt';

// LOAD
$queueRaw = trim(file_get_contents($queueFile));
$stackRaw = trim(file_get_contents($stackFile));

$queueItems = $queueRaw === "" ? [] : array_filter(array_map('json_decode', explode("\n", $queueRaw)));
$stackItems = $stackRaw === "" ? [] : array_filter(array_map('json_decode', explode("\n", $stackRaw)));

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Sistem Rental Peralatan Dapur</title>
<link rel="stylesheet" href="style.css">
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
    <!-- Tombol Back sederhana -->
    <a href="index.php" class="cta" style="margin-bottom:16px; display:inline-block;">← Kembali ke Halaman Utama</a>
    <div class="grid-two">

        <!-- ANTRIAN -->
        <section class="card half">
            <h3>Antrian Permintaan</h3>

            <div class="box">
                <?php if (empty($queueItems)): ?>
                    <div class="empty">Belum ada permintaan.</div>
                <?php endif; ?>

                <?php foreach ($queueItems as $q): ?>
                    <div class="item">
                        <div class="name"><?= htmlspecialchars($q->customer) ?></div>
                        <div class="meta">
                            Item ID: <?= $q->item_id ?> |
                            Jumlah: <?= $q->quantity ?> |
                            Durasi: <?= $q->duration ?> hari
                        </div>
                        <div class="time"><?= $q->time ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- RIWAYAT APPROVE -->
        <section class="card half">
            <h3>Riwayat Approve (LIFO)</h3>

            <div class="box proc">
                <?php if (!empty($stackItems)) $stackItems = array_reverse($stackItems); ?>

                <?php if (empty($stackItems)): ?>
                    <div class="empty">Belum ada riwayat approve.</div>
                <?php endif; ?>

                <?php foreach ($stackItems as $s): ?>
                    <div class="item processed">
                        <div class="name">
                            <?= htmlspecialchars($s->customer) ?>
                            <span class="badge">LIFO</span>
                        </div>
                        <div class="meta">
                            Item ID: <?= $s->item_id ?> |
                            Durasi: <?= $s->duration ?> hari
                        </div>
                        <div class="time">
                            Approved pada: <?= $s->approved_time ?? '-' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

    </div>

</main>

</body>
</html>
