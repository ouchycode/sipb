<script setup>
import { Link, Head } from "@inertiajs/vue3";
import {
    Boxes,
    CheckCircle2,
    Clock3,
    MapPin,
    Search,
    ShieldCheck,
    Sparkles,
    UserRound,
} from "@lucide/vue";
import AppLayout from "../../Shared/AppLayout.vue";

const campusLogo = "/assets/logo-uym.png";

defineProps({
    stats: {
        type: Object,
        default: () => ({
            posted: 0,
            recovered: 0,
            active: 0,
        }),
    },
});
</script>

<template>
    <Head>
        <title>Beranda SIPB UYM - Sistem Informasi Penemuan Barang</title>
        <meta head-key="description" name="description" content="Platform lost and found kampus Universitas Yatsi Madani untuk melihat daftar barang temuan dan membantu proses pengambilan lewat validasi admin." />
        <meta head-key="og:title" property="og:title" content="Beranda SIPB UYM - Sistem Informasi Penemuan Barang" />
        <meta head-key="og:description" property="og:description" content="Platform lost and found kampus Universitas Yatsi Madani untuk melihat daftar barang temuan dan membantu proses pengambilan." />
        <meta head-key="og:image" property="og:image" content="/assets/logo-uym.png" />
    </Head>
    <AppLayout title="Beranda SIPB UYM" :show-page-header="false">
        <section class="mx-auto flex min-h-[calc(100vh-180px)] max-w-5xl flex-col items-center justify-center px-4 py-12 text-center md:py-16">
            <div class="mb-6 flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-md bg-[#edf2ff] text-[#2737c9]">
                    <img :src="campusLogo" alt="Universitas Yatsi Madani" class="h-9 w-9 object-contain" />
                </span>
                <span class="text-4xl font-extrabold tracking-tight text-[#1a2134]">
                    SIPB UYM
                </span>
            </div>

            <h1 class="max-w-4xl text-4xl font-extrabold leading-tight tracking-tight text-[#1a2134] md:text-5xl">
                Barang tertinggal? <span class="text-[#2737c9]">Cari di SIPB.</span>
            </h1>
            <p class="mt-6 max-w-2xl text-base font-medium leading-7 text-[#64748b]">
                Platform lost and found kampus untuk melihat daftar barang
                temuan dan membantu proses pengambilan
                lewat validasi admin.
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <Link
                    href="/cari"
                    class="inline-flex h-10 items-center gap-2 rounded-md bg-[#2737c9] px-5 text-sm font-bold text-white hover:bg-[#1f2dad]"
                >
                    <Search class="h-4 w-4" />
                    Cari Barang
                </Link>

            </div>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-5 text-sm font-semibold text-[#64748b]">
                <span class="inline-flex items-center gap-2">
                    <Boxes class="h-4 w-4 text-[#2737c9]" />
                    <strong class="text-[#1a2134]">{{ stats.posted }}</strong>
                    barang dipublikasi
                </span>
                <span>&middot;</span>
                <span class="inline-flex items-center gap-2">
                    <CheckCircle2 class="h-4 w-4 text-[#00bf8e]" />
                    <strong class="text-[#1a2134]">{{ stats.recovered }}</strong>
                    barang kembali
                </span>
                <span>&middot;</span>
                <span class="inline-flex items-center gap-2">
                    <UserRound class="h-4 w-4 text-[#7957d5]" />
                    <strong class="text-[#1a2134]">{{ stats.active }}</strong>
                    barang aktif
                </span>
            </div>
        </section>

        <section class="border-y border-[#e6e9ed] py-16">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.28em] text-[#2737c9]">
                        Alat Siap Pakai
                    </p>
                    <h2 class="mt-4 max-w-xl text-3xl font-extrabold leading-tight text-[#1a2134]">
                        Dibuat agar proses pengembalian barang terasa jelas,
                        cepat, dan terpercaya.
                    </h2>
                </div>
                <p class="text-sm font-medium leading-7 text-[#64748b]">
                    SIPB membantu mahasiswa menemukan barang yang sudah masuk
                    ke admin. Laporan barang temuan dibuat melalui QR yang
                    diberikan petugas di lokasi layanan.
                </p>
            </div>

            <div class="mx-auto mt-10 grid max-w-6xl gap-4 px-4 lg:grid-cols-[minmax(0,1.2fr)_1fr]">
                <article class="sipb-panel p-8">
                    <div class="mb-6 grid h-12 w-12 place-items-center rounded-md bg-[#2737c9] text-white">
                        <Sparkles class="h-6 w-6" />
                    </div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-[#2737c9]">
                        Fitur Utama
                    </p>
                    <h3 class="mt-4 text-3xl font-extrabold text-[#1a2134]">
                        Pencarian Barang Terstruktur
                    </h3>
                    <p class="mt-5 max-w-xl text-sm font-medium leading-7 text-[#64748b]">
                        Cari barang berdasarkan kata kunci, kategori, lokasi,
                        dan tanggal ditemukan agar mahasiswa bisa menemukan
                        informasi yang relevan lebih cepat.
                    </p>
                    <p class="mt-8 text-xs font-medium leading-6 text-[#64748b]">
                        Cocok untuk data barang yang detailnya tidak selalu
                        lengkap, tetapi tetap perlu mudah ditelusuri oleh
                        mahasiswa dan petugas kampus.
                    </p>
                </article>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2">
                    <article class="sipb-panel p-5">
                        <div class="mb-5 grid h-10 w-10 place-items-center rounded-md bg-[#d93c3c] text-white">
                            <Search class="h-5 w-5" />
                        </div>
                        <h3 class="font-extrabold text-[#1a2134]">Cari & Filter</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                            Temukan barang dari nama, deskripsi, lokasi, atau
                            tanggal.
                        </p>
                    </article>
                    <article class="sipb-panel p-5">
                        <div class="mb-5 grid h-10 w-10 place-items-center rounded-md bg-[#00bf8e] text-white">
                            <MapPin class="h-5 w-5" />
                        </div>
                        <h3 class="font-extrabold text-[#1a2134]">Lokasi Jelas</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                            Setiap barang punya lokasi temuan dan informasi
                            penyimpanan.
                        </p>
                    </article>
                    <article class="sipb-panel p-5">
                        <div class="mb-5 grid h-10 w-10 place-items-center rounded-md bg-[#2737c9] text-white">
                            <Clock3 class="h-5 w-5" />
                        </div>
                        <h3 class="font-extrabold text-[#1a2134]">Status Terkini</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                            Admin bisa mengubah status dari pending sampai
                            selesai.
                        </p>
                    </article>
                    <article class="sipb-panel p-5">
                        <div class="mb-5 grid h-10 w-10 place-items-center rounded-md bg-[#00bf8e] text-white">
                            <ShieldCheck class="h-5 w-5" />
                        </div>
                        <h3 class="font-extrabold text-[#1a2134]">Validasi Aman</h3>
                        <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                            Barang dikembalikan setelah admin memvalidasi
                            kepemilikan.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-16 text-center">
            <h2 class="text-3xl font-extrabold text-[#1a2134]">
                Cara Kerja
            </h2>
            <p class="mt-4 text-sm font-medium text-[#64748b]">
                Ikuti langkah sederhana untuk membantu barang kembali ke
                pemiliknya.
            </p>

            <div class="mt-10 grid gap-6 md:grid-cols-3">
                <article class="sipb-panel p-6 text-left">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-[#2737c9] text-sm font-bold text-white">1</span>
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-[#5ac8ee] text-white">
                            <Search class="h-4 w-4" />
                        </span>
                    </div>
                    <h3 class="text-lg font-extrabold text-[#1a2134]">Datang ke Admin</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                        Penemu barang datang ke petugas dan mengisi laporan
                        lewat QR yang diberikan admin.
                    </p>
                </article>
                <article class="sipb-panel p-6 text-left">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-[#2737c9] text-sm font-bold text-white">2</span>
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-[#feae37] text-white">
                            <Sparkles class="h-4 w-4" />
                        </span>
                    </div>
                    <h3 class="text-lg font-extrabold text-[#1a2134]">Admin Publikasi</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                        Admin memeriksa laporan dan menampilkan barang yang
                        siap dicari mahasiswa.
                    </p>
                </article>
                <article class="sipb-panel p-6 text-left">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-[#2737c9] text-sm font-bold text-white">3</span>
                        <span class="grid h-8 w-8 place-items-center rounded-md bg-[#00bf8e] text-white">
                            <CheckCircle2 class="h-4 w-4" />
                        </span>
                    </div>
                    <h3 class="text-lg font-extrabold text-[#1a2134]">Ambil Barang</h3>
                    <p class="mt-3 text-sm font-medium leading-6 text-[#64748b]">
                        Pemilik datang ke petugas dan melakukan validasi
                        sebelum barang diserahkan.
                    </p>
                </article>
            </div>
        </section>

        <section class="border-y border-[#e6e9ed] px-4 py-16">
            <div class="sipb-panel mx-auto max-w-3xl p-10 text-center">
                <h2 class="text-3xl font-extrabold text-[#1a2134]">
                    Siap menemukan barangmu?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-sm font-medium leading-6 text-[#64748b]">
                    Cari daftar barang temuan yang sudah dipublikasi. Jika kamu
                    menemukan barang, serahkan ke admin agar dicatat di sistem.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Link
                        href="/cari"
                        class="inline-flex h-10 items-center rounded-md bg-[#2737c9] px-5 text-sm font-bold text-white hover:bg-[#1f2dad]"
                    >
                        Cari Barang
                    </Link>
                </div>
            </div>
        </section>

        <footer class="py-9 text-center">
            <div class="flex items-center justify-center gap-2">
                <span class="grid h-7 w-7 place-items-center rounded-md bg-[#edf2ff]">
                    <img :src="campusLogo" alt="" class="h-6 w-6 object-contain" />
                </span>
                <span class="font-extrabold text-[#1a2134]">SIPB UYM</span>
            </div>
            <p class="mt-4 text-xs font-medium text-[#64748b]">
                &copy; 2026 SIPB UYM. Dibuat untuk layanan lost and found kampus.
            </p>
            <div class="mt-4 flex justify-center gap-5 text-xs font-semibold text-[#2737c9]">
                <Link href="/cari">Cari Barang</Link>
                <Link href="/bantuan">Bantuan</Link>
            </div>
        </footer>
    </AppLayout>
</template>
