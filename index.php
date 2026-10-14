<?php
$pesan = '';
$jenisPesan = 'success';
$laporan = [];
$kategoriList = [];

function e($nilai) {
    return htmlspecialchars((string)$nilai, ENT_QUOTES, 'UTF-8');
}

try {
    $db = new PDO(
        "mysql:host=localhost;dbname=db_laporan_fasilitas;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Menyimpan laporan baru
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nama = trim($_POST['nama_pelapor'] ?? '');
        $fasilitas = trim($_POST['nama_fasilitas'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');
        $kategori = filter_input(
            INPUT_POST,
            'id_kategori',
            FILTER_VALIDATE_INT
        );
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $prioritas = $_POST['prioritas'] ?? '';

        if (
            $nama === '' || $fasilitas === '' ||
            $lokasi === '' || !$kategori || $deskripsi === '' ||
            !in_array($prioritas, ['Rendah', 'Sedang', 'Tinggi'], true)
        ) {
            $pesan = 'Mohon lengkapi semua data dengan benar.';
            $jenisPesan = 'error';
        } else {
            $cek = $db->prepare(
                "SELECT COUNT(*) FROM kategori_fasilitas
                 WHERE id_kategori = ?"
            );
            $cek->execute([$kategori]);

            if (!$cek->fetchColumn()) {
                $pesan = 'Kategori tidak ditemukan.';
                $jenisPesan = 'error';
            } else {
                $sql = "INSERT INTO laporan_kerusakan
                    (nama_pelapor, nama_fasilitas, lokasi,
                     id_kategori, deskripsi, prioritas)
                    VALUES (?, ?, ?, ?, ?, ?)";

                $db->prepare($sql)->execute([
                    $nama, $fasilitas, $lokasi,
                    $kategori, $deskripsi, $prioritas
                ]);

                header('Location: index.php?berhasil=1');
                exit;
            }
        }
    }

    // Mengambil kategori dari database
    $kategoriList = $db->query(
        "SELECT * FROM kategori_fasilitas ORDER BY nama_kategori"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil seluruh laporan
    $sql = "SELECT l.*, k.nama_kategori
            FROM laporan_kerusakan l
            JOIN kategori_fasilitas k
            ON l.id_kategori = k.id_kategori
            ORDER BY l.tanggal_laporan DESC";

    $laporan = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $err) {
    error_log($err->getMessage());
    $pesan = 'Database gagal diakses. Periksa konfigurasi database.';
    $jenisPesan = 'error';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelaporan Kerusakan Fasilitas</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800 min-h-screen">

<header class="bg-white border-b">
    <div class="max-w-6xl mx-auto px-4 py-6">
        <h1 class="text-2xl font-bold">
            Pelaporan Kerusakan Fasilitas
        </h1>
        <p class="text-gray-500 mt-2">
            Sistem pelaporan fasilitas sekolah.
        </p>
        <nav class="flex gap-5 mt-4 text-sm">
            <a href="#form" class="text-blue-600 hover:underline">
                Buat Laporan
            </a>
            <a href="#daftar" class="text-blue-600 hover:underline">
                Daftar Laporan
            </a>
        </nav>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <?php if (isset($_GET['berhasil'])): ?>
        <div class="bg-green-100 text-green-700 rounded-lg p-4">
            Laporan berhasil disimpan.
        </div>
    <?php endif; ?>

    <?php if ($pesan !== ''): ?>
        <div class="rounded-lg p-4 <?= $jenisPesan === 'error'
            ? 'bg-red-100 text-red-700'
            : 'bg-green-100 text-green-700' ?>">
            <?= e($pesan) ?>
        </div>
    <?php endif; ?>

    <!-- FORM LAPORAN -->
    <section id="form" class="bg-white border rounded-xl p-6">
        <h2 class="text-xl font-bold mb-5">Form Pelaporan</h2>

        <form method="POST" id="form-laporan"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <?php
            $fields = [
                'nama_pelapor' => 'Nama Pelapor',
                'nama_fasilitas' => 'Nama Fasilitas',
                'lokasi' => 'Lokasi Kerusakan'
            ];

            foreach ($fields as $name => $label):
            ?>
                <div>
                    <label for="<?= $name ?>"
                        class="block text-sm font-medium mb-1">
                        <?= $label ?>
                    </label>
                    <input type="text" id="<?= $name ?>"
                        name="<?= $name ?>" maxlength="100" required
                        value="<?= e($_POST[$name] ?? '') ?>"
                        class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
            <?php endforeach; ?>

            <div>
                <label for="id_kategori"
                    class="block text-sm font-medium mb-1">
                    Kategori Fasilitas
                </label>
                <select name="id_kategori" id="id_kategori" required
                    class="w-full border rounded-lg p-2 bg-white">
                    <option value="">Pilih kategori</option>
                    <?php foreach ($kategoriList as $k): ?>
                        <option value="<?= e($k['id_kategori']) ?>"
                            <?= (string)($_POST['id_kategori'] ?? '') ===
                                (string)$k['id_kategori'] ? 'selected' : '' ?>>
                            <?= e($k['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="prioritas"
                    class="block text-sm font-medium mb-1">
                    Prioritas
                </label>
                <select name="prioritas" id="prioritas" required
                    class="w-full border rounded-lg p-2 bg-white">
                    <?php foreach (['Rendah', 'Sedang', 'Tinggi'] as $p): ?>
                        <option value="<?= $p ?>"
                            <?= ($_POST['prioritas'] ?? 'Sedang') === $p
                                ? 'selected' : '' ?>>
                            <?= $p ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="deskripsi"
                    class="block text-sm font-medium mb-1">
                    Deskripsi Kerusakan
                </label>
                <textarea name="deskripsi" id="deskripsi"
                    rows="4" maxlength="1000" required
                    class="w-full border rounded-lg p-2"
                    placeholder="Jelaskan kerusakan..."><?= e($_POST['deskripsi'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    <span id="karakter">0</span>/1000 karakter
                </p>
            </div>

            <div class="md:col-span-2 flex gap-3">
                <button type="submit"
                    class="bg-blue-600 text-white rounded-lg px-5 py-2 hover:bg-blue-700">
                    Kirim Laporan
                </button>
                <button type="reset"
                    class="border rounded-lg px-5 py-2 hover:bg-gray-50">
                    Reset Form
                </button>
            </div>
        </form>
    </section>

    <!-- DAFTAR LAPORAN -->
    <section id="daftar" class="bg-white border rounded-xl p-6">
        <h2 class="text-xl font-bold">Daftar Laporan</h2>
        <p class="text-sm text-gray-500 mt-1 mb-5">
            Cari dan filter laporan yang tersedia.
        </p>

        <div class="grid md:grid-cols-3 gap-3 mb-4">
            <input id="cari" type="search"
                placeholder="Cari laporan..."
                class="border rounded-lg p-2">

            <select id="filter-status" class="border rounded-lg p-2 bg-white">
                <option value="">Semua Status</option>
                <option value="Menunggu">Menunggu</option>
                <option value="Diproses">Diproses</option>
                <option value="Selesai">Selesai</option>
            </select>

            <select id="filter-prioritas" class="border rounded-lg p-2 bg-white">
                <option value="">Semua Prioritas</option>
                <option value="Rendah">Rendah</option>
                <option value="Sedang">Sedang</option>
                <option value="Tinggi">Tinggi</option>
            </select>
        </div>

        <div class="flex justify-between items-center mb-3">
            <p id="jumlah" class="text-sm text-gray-500"></p>
            <button id="reset-filter" type="button"
                class="border rounded-lg px-3 py-2 text-sm hover:bg-gray-50">
                Reset Filter
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[750px] text-sm text-left">
                <thead class="bg-gray-50 border-y">
                    <tr>
                        <th class="p-3">Fasilitas</th>
                        <th class="p-3">Kategori</th>
                        <th class="p-3">Lokasi</th>
                        <th class="p-3">Prioritas</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($laporan as $l): ?>
                        <tr class="baris hover:bg-gray-50"
                            data-cari="<?= e($l['nama_fasilitas'].' '.$l['nama_pelapor'].' '.$l['lokasi'].' '.$l['nama_kategori'].' '.$l['deskripsi']) ?>"
                            data-status="<?= e($l['status']) ?>"
                            data-prioritas="<?= e($l['prioritas']) ?>">

                            <td class="p-3"><?= e($l['nama_fasilitas']) ?></td>
                            <td class="p-3"><?= e($l['nama_kategori']) ?></td>
                            <td class="p-3"><?= e($l['lokasi']) ?></td>
                            <td class="p-3"><?= e($l['prioritas']) ?></td>
                            <td class="p-3"><?= e($l['status']) ?></td>
                            <td class="p-3">
                                <button type="button"
                                    class="detail text-blue-600 hover:underline"
                                    data-info="<?= e(
                                        'Fasilitas: '.$l['nama_fasilitas'].
                                        "\nKategori: ".$l['nama_kategori'].
                                        "\nPelapor: ".$l['nama_pelapor'].
                                        "\nLokasi: ".$l['lokasi'].
                                        "\nPrioritas: ".$l['prioritas'].
                                        "\nStatus: ".$l['status'].
                                        "\nDeskripsi: ".$l['deskripsi']
                                    ) ?>">
                                    Lihat Detail
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p id="kosong" class="hidden text-center text-gray-500 py-6">
            Tidak ada laporan yang cocok.
        </p>
    </section>
</main>

<footer class="bg-white border-t mt-8 p-5 text-center text-sm text-gray-500">
    Sistem Pelaporan Kerusakan Fasilitas Sekolah
</footer>

<script>
const cari = document.getElementById('cari');
const status = document.getElementById('filter-status');
const prioritas = document.getElementById('filter-prioritas');
const baris = [...document.querySelectorAll('.baris')];

function filterLaporan() {
    let total = 0;

    baris.forEach(tr => {
        const cocok = tr.dataset.cari.toLowerCase()
            .includes(cari.value.toLowerCase()) &&
            (!status.value || tr.dataset.status === status.value) &&
            (!prioritas.value || tr.dataset.prioritas === prioritas.value);

        tr.hidden = !cocok;
        if (cocok) total++;
    });

    document.getElementById('jumlah').textContent =
        `${total} dari ${baris.length} laporan`;
    document.getElementById('kosong').classList.toggle('hidden', total > 0);
}

cari.addEventListener('input', filterLaporan);
status.addEventListener('change', filterLaporan);
prioritas.addEventListener('change', filterLaporan);

document.getElementById('reset-filter').addEventListener('click', () => {
    cari.value = '';
    status.value = '';
    prioritas.value = '';
    filterLaporan();
});

const deskripsi = document.getElementById('deskripsi');
const karakter = document.getElementById('karakter');

function hitungKarakter() {
    karakter.textContent = deskripsi.value.length;
}
deskripsi.addEventListener('input', hitungKarakter);
document.getElementById('form-laporan').addEventListener('reset', () => {
    setTimeout(hitungKarakter, 0);
});
hitungKarakter();

document.querySelectorAll('.detail').forEach(btn => {
    btn.addEventListener('click', () => alert(btn.dataset.info));
});

document.getElementById('form-laporan').addEventListener('submit', event => {
    if (!confirm('Apakah data laporan sudah benar?')) {
        event.preventDefault();
    }
});

filterLaporan();
</script>

</body>
</html>