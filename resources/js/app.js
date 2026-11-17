import Alpine from 'alpinejs';

const format = (n) => (n === '' || n === null || n === undefined ? '' : new Intl.NumberFormat('id-ID').format(n));

window.rupiah = (n) => 'Rp ' + (format(Math.round(n || 0)) || '0');

// Isian rupiah: yang tampil memakai titik ribuan, yang dikirim ke server angka polos.
Alpine.data('isianRupiah', (awal = '') => ({
    nilai: awal === null || awal === '' ? '' : Number(awal),
    format,
    ketik(e) {
        const digit = e.target.value.replace(/\D/g, '');
        this.nilai = digit === '' ? '' : parseInt(digit, 10);
        e.target.value = format(this.nilai);
        this.$dispatch('nilai', this.nilai === '' ? 0 : this.nilai);
    },
}));

// Pencarian anggota: ketik nama atau ID anggota, lalu pilih dari daftar.
Alpine.data('pilihAnggota', (daftar = [], terpilih = null) => ({
    daftar,
    buka: false,
    cari: '',
    terpilih: null,
    init() {
        const awal = this.daftar.find((a) => String(a.id) === String(terpilih));
        if (awal) this.pilih(awal);
    },
    get hasil() {
        const k = this.cari.trim().toLowerCase();
        if (!k || this.terpilih) return this.daftar;
        return this.daftar.filter((a) => a.nama.toLowerCase().includes(k) || a.kode.toLowerCase().includes(k));
    },
    pilih(a) {
        if (!a) return;
        this.terpilih = a;
        this.cari = `${a.kode} · ${a.nama}`;
        this.buka = false;
        this.$dispatch('anggota', a.id);
    },
    kosongkan() {
        this.terpilih = null;
        this.buka = true;
        this.$dispatch('anggota', null);
    },
}));

window.Alpine = Alpine;
Alpine.start();
