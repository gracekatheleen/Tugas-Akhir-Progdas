<?php
session_start();

// FILES
$queueFile     = __DIR__ . '/queue.txt';
$processedFile = __DIR__ . '/processed.txt';
$inventoryFile = __DIR__ . '/inventory.json';
$stackFile = __DIR__ . '/stack.txt';

// Buat file jika belum ada
if (!file_exists($queueFile)) file_put_contents($queueFile, "");
if (!file_exists($processedFile)) file_put_contents($processedFile, "");
if (!file_exists($stackFile)) file_put_contents($stackFile, "");

if (!file_exists($inventoryFile)) {   // MODUL 1: VARIABEL, TIPE DATA, DAN ARRAY
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

// CLEAR HISTORY - MODUL 2: PENGKONDISIAN
if (($_POST['action'] ?? '') === 'clear_history') {
    file_put_contents($queueFile, "");
    file_put_contents($processedFile, "");
    $_SESSION['flash'] = "Riwayat dibersihkan.";
    header("Location: index.php");
    exit;
}

// INPUT VALIDATION
$nama   = trim($_POST['nama']   ?? '');
$hp     = trim($_POST['hp']     ?? '');
$alatId = intval($_POST['alat'] ?? 0);
$jumlah = intval($_POST['jumlah'] ?? 0);
$durasi = intval($_POST['durasi'] ?? 0);

$errors = [];
if ($nama === '')        $errors[] = "Nama harus diisi.";
if ($alatId <= 0)        $errors[] = "Pilih peralatan.";
if ($jumlah <= 0)        $errors[] = "Jumlah tidak valid.";
if ($durasi <= 0)        $errors[] = "Durasi tidak valid.";

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit;
}

// LOAD INVENTORY - MODUL 4: FUNCTION DAN METHOD
class RentalItem {
    public int $id;
    public string $name;
    public int $price;
    public int $stock;

    public function __construct(int $id, string $name, int $price, int $stock) {
        $this->id    = $id;
        $this->name  = $name;
        $this->price = $price;
        $this->stock = $stock;
    }
}

$inventory = json_decode(file_get_contents($inventoryFile), true);
$found = null;

foreach ($inventory as $it) {
    if (intval($it['id']) === $alatId) {
        $found = new RentalItem($it['id'], $it['name'], $it['price'], $it['stock']);
        break;
    }
}

if (!$found) {
    $_SESSION['errors'] = ["Peralatan tidak ditemukan."];
    header("Location: index.php");
    exit;
}

// CEK STOK
if ($jumlah > $found->stock) {
    $_SESSION['errors'] = ["Stok tidak cukup. Tersedia: {$found->stock}"];
    header("Location: index.php");
    exit;
}

// BUAT ORDER → FIFO
$total = $found->price * $jumlah * $durasi;
$timestamp = date('Y-m-d H:i:s');
$orderId = uniqid('ORD_');

$orderObj = (object)[
    'id'       => $orderId,
    'customer' => $nama,
    'phone'    => $hp,
    'item_id'  => $found->id,
    'item_name'=> $found->name,
    'quantity' => $jumlah,
    'duration' => $durasi,
    'total'    => $total,
    'time'     => $timestamp,
    'status'   => 'processed'  // LANGSUNG PROCESSED
];
// MODUL 7: STACK DAN QUEUE
// MASUKKAN KE QUEUE (LOG FIFO)
file_put_contents($queueFile, json_encode($orderObj, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// MASUKKAN KE PROCESSED
file_put_contents($processedFile, json_encode($orderObj, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// TAMBAHKAN KE STACK (LIFO)
file_put_contents($stackFile, json_encode($orderObj, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// UPDATE STOK - MODUL 3: PERULANGAN
foreach ($inventory as &$it) {
    if (intval($it['id']) === $found->id) {
        $it['stock'] = max(0, $it['stock'] - $jumlah);
        break;
    }
}

file_put_contents(
    $inventoryFile,
    json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

// SUCCESS MESSAGE
$_SESSION['flash'] = "Pesanan diproses otomatis (FIFO) dan telah masuk ke daftar processed.";

// Redirect kembali ke halaman utama
header("Location: index.php");
exit;
