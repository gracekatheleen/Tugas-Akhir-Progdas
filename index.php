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

    <!-- GRID 2 KOLOM -->
    <div class="grid-two">

        <!-- FORM -->
        <section class="card half">
            <h2>Pilih item & ajukan sewa (Customer)</h2>

            <?php if ($errors): ?>
                <div class="errors">
                    <?php foreach ($errors as $e): ?>
                        <div class="err"><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="process.php?redir=antrian" method="POST" class="form">

                <label>Nama Pemesan</label>
                <input type="text" name="nama" required placeholder="Nama lengkap">

                <label>No HP (opsional)</label>
                <input type="text" name="hp" placeholder="08xxxx">

                <label>Pilih Peralatan</label>
                <select name="alat" required>
                    <option value="">-- Pilih alat --</option>
                    <?php foreach ($inventory as $it): 
                        $opt = $it['name'] . " — Rp" . number_format($it['price']) . " — Stok: " . $it['stock'];
                    ?>
                        <option value="<?= $it['id'] ?>"><?= htmlspecialchars($opt) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="two">
                    <div>
                        <label>Jumlah</label>
                        <input type="number" min="1" name="jumlah" value="1" required>
                    </div>
                    <div>
                        <label>Durasi (hari)</label>
                        <input type="number" min="1" name="durasi" value="1" required>
                    </div>
                </div>

                <button class="cta">Ajukan Sewa</button>

            </form>
        </section>

        <!-- INVENTORY -->
        <section class="card half">
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
        </section>

    </div> <!-- END GRID-TWO -->

    <!-- KATALOG -->
    <section class="card" style="margin-top: 26px;">
        <h2>Katalog Peralatan</h2>

        <div class="katalog-row">

            <div class="katalog-item">
                <img src="kompor.jpg" class="katalog-img">
                <div class="katalog-name">Kompor Portable Gas</div>
            </div>

            <div class="katalog-item">
                <img src="wajan.jpg" class="katalog-img">
                <div class="katalog-name">Wajan Besar</div>
            </div>

            <div class="katalog-item">
                <img src="ricecooker.png" class="katalog-img">
                <div class="katalog-name">Rice Cooker 5L</div>
            </div>

            <div class="katalog-item">
                <img src="blender.jpg" class="katalog-img">
                <div class="katalog-name">Blender</div>
            </div>

            <div class="katalog-item">
                <img src="mixer.jpg" class="katalog-img">
                <div class="katalog-name">Mixer</div>
            </div>

            <div class="katalog-item">
                <img src="panci.jpeg" class="katalog-img">
                <div class="katalog-name">Panci</div>
            </div>

        </div>
    </section>
</main>

<footer class="foot">
    <div class="credit">TA Pemrograman Dasar • Universitas Diponegoro</div>
</footer>
</body>
</html>
