<script setup>
import { onMounted } from "vue";
import { formatDate } from "../../Shared/status";

defineProps({
    item: Object,
});

onMounted(() => {
    window.setTimeout(() => window.print(), 300);
});
</script>

<template>
    <main class="min-h-screen bg-white p-8 text-[#1a2134]">
        <section class="mx-auto max-w-3xl">
            <div class="border-b-2 border-[#1a2134] pb-4 text-center">
                <h1 class="text-2xl font-extrabold">Tanda Terima Pengambilan Barang</h1>
                <p class="mt-1 text-sm">SIPB UYM - Universitas Yatsi Madani</p>
            </div>

            <dl class="mt-8 grid gap-4 text-sm sm:grid-cols-2">
                <div><dt class="font-bold">Kode</dt><dd>SIPB-{{ item.id }}</dd></div>
                <div><dt class="font-bold">Tanggal cetak</dt><dd>{{ formatDate(new Date().toISOString()) }}</dd></div>
                <div><dt class="font-bold">Nama barang</dt><dd>{{ item.name }}</dd></div>
                <div><dt class="font-bold">Kategori</dt><dd>{{ item.category }}</dd></div>
                <div><dt class="font-bold">Lokasi</dt><dd>{{ item.location }}</dd></div>
                <div><dt class="font-bold">Tanggal laporan</dt><dd>{{ formatDate(item.found_at) }}</dd></div>
                <div><dt class="font-bold">Pengambil</dt><dd>{{ item.claimant_name || '-' }}</dd></div>
                <div><dt class="font-bold">NIM pengambil</dt><dd>{{ item.claimant_nim || '-' }}</dd></div>
                <div><dt class="font-bold">Admin</dt><dd>{{ item.manager?.name || '-' }}</dd></div>
                <div><dt class="font-bold">Waktu diambil</dt><dd>{{ formatDate(item.claimed_at) }}</dd></div>
            </dl>

            <div class="mt-8">
                <p class="font-bold">Catatan validasi</p>
                <p class="mt-2 min-h-20 border border-[#cbd5e1] p-3 text-sm">{{ item.validation_notes || '-' }}</p>
            </div>

            <div class="mt-16 grid grid-cols-2 gap-12 text-center text-sm">
                <div>
                    <p>Admin</p>
                    <div class="mt-16 border-t border-[#1a2134] pt-2">{{ item.manager?.name || 'Admin SIPB' }}</div>
                </div>
                <div>
                    <p>Pengambil</p>
                    <div class="mt-16 border-t border-[#1a2134] pt-2">{{ item.claimant_name || 'Pemilik Barang' }}</div>
                </div>
            </div>
        </section>
    </main>
</template>
