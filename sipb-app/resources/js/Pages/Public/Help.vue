<script setup>
import { Link } from "@inertiajs/vue3";
import {
    ArrowLeft,
    ClipboardCheck,
    HelpCircle,
    MapPin,
    Search,
} from "@lucide/vue";
import AppLayout from "../../Shared/AppLayout.vue";

const quickSteps = [
    {
        title: "Cari barang",
        description: "Gunakan kata kunci, kategori, lokasi, tanggal, atau urutan terbaru di halaman cari.",
        icon: Search,
    },
    {
        title: "Datang ke resepsionis",
        description: "Jika barang cocok, datang ke resepsionis untuk proses validasi langsung.",
        icon: MapPin,
    },
    {
        title: "Validasi di tempat",
        description: "Admin mencocokkan identitas dan bukti kepemilikan langsung di resepsionis.",
        icon: ClipboardCheck,
    },
];

const faqs = [
    ["Apakah saya bisa melaporkan barang hilang atau ditemukan lewat website?", "Tidak bisa. Website ini hanya berfungsi sebagai etalase barang yang telah ditemukan. Jika Anda menemukan atau kehilangan barang, silakan datang langsung ke resepsionis."],
    ["Siapa yang memasukkan data barang ke sistem?", "Seluruh pendataan dan input barang ke dalam website dilakukan sepenuhnya oleh Admin di resepsionis."],
    ["Bagaimana cara klaim barang?", "Cari barang Anda di website ini. Jika ada yang cocok, silakan datang ke resepsionis untuk mencocokkan identitas dan mengklaim barang Anda."],
    ["Apa yang harus dibawa saat mengambil barang?", "Bawa kartu identitas kampus dan sebutkan bukti kepemilikan (seperti ciri khusus, isi barang, atau bukti foto pendukung)."],
    ["Kenapa barang yang saya serahkan ke resepsionis belum muncul di website?", "Barang yang baru diserahkan mungkin membutuhkan sedikit waktu bagi Admin untuk memotret dan menginput datanya ke dalam sistem."],
];
</script>

<template>
    <AppLayout title="Bantuan SIPB UYM" :show-page-header="false" :show-public-sidebar="false">
        <section class="mx-auto max-w-5xl">
            <Link href="/" class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-[#747a8b] hover:text-[#2737c9]">
                <ArrowLeft class="h-4 w-4" />
                Kembali ke beranda
            </Link>

            <div class="mb-8">
                <p class="text-xs font-extrabold uppercase tracking-[0.26em] text-[#2737c9]">Pusat Bantuan</p>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-[#1a2134] md:text-4xl">Bantuan SIPB UYM</h1>
                <p class="mt-3 max-w-2xl text-sm font-medium leading-7 text-[#64748b]">
                    Panduan singkat untuk mencari barang, mengambil barang, dan memahami status laporan.
                </p>
            </div>

            <section class="mb-6 grid gap-4 md:grid-cols-3">
                <article v-for="step in quickSteps" :key="step.title" class="sipb-panel p-5">
                    <span class="grid h-10 w-10 place-items-center rounded-md bg-[#2737c9] text-white">
                        <component :is="step.icon" class="h-5 w-5" />
                    </span>
                    <h2 class="mt-4 font-extrabold text-[#1a2134]">{{ step.title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-[#64748b]">{{ step.description }}</p>
                </article>
            </section>

            <section class="mb-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
                <div class="sipb-panel p-5 md:p-6">
                    <div class="flex items-center gap-2">
                        <HelpCircle class="h-5 w-5 text-[#2737c9]" />
                        <h2 class="text-xl font-extrabold text-[#1a2134]">Pertanyaan umum</h2>
                    </div>
                    <div class="mt-5 space-y-3">
                        <details v-for="[question, answer] in faqs" :key="question" class="rounded-md bg-[#f6f7fa] p-4">
                            <summary class="cursor-pointer font-extrabold text-[#1a2134]">{{ question }}</summary>
                            <p class="mt-3 text-sm leading-6 text-[#64748b]">{{ answer }}</p>
                        </details>
                    </div>
                </div>

                <aside class="space-y-6">
                    <section class="sipb-accent-panel p-5">
                        <h2 class="text-lg font-extrabold text-[#1a2134]">Butuh cek barang?</h2>
                        <p class="mt-2 text-sm leading-6 text-[#64748b]">
                            Mulai dari halaman cari. Jika ada barang yang cocok, datang ke resepsionis untuk validasi pengambilan.
                        </p>
                        <Link href="/cari" class="sipb-button-primary mt-4 inline-flex w-full items-center justify-center gap-2">
                            <Search class="h-4 w-4" />
                            Cari barang
                        </Link>
                    </section>
                </aside>
            </section>
        </section>
    </AppLayout>
</template>
