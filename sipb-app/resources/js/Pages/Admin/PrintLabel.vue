<script setup>
import { onMounted } from "vue";
import { formatDate } from "../../Shared/status";

const props = defineProps({
    item: Object,
    publicUrl: String,
    expiresAt: String,
});

const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(props.publicUrl)}`;

onMounted(() => {
    window.setTimeout(() => window.print(), 300);
});
</script>

<template>
    <main class="min-h-screen bg-white p-8 text-[#1a2134]">
        <section class="mx-auto w-[420px] border border-[#1a2134] p-6 text-center">
            <p class="text-xs font-extrabold uppercase tracking-[0.24em]">SIPB UYM</p>
            <h1 class="mt-3 text-2xl font-extrabold">{{ item.name }}</h1>
            <p class="mt-1 text-sm font-semibold">{{ item.category }} - {{ item.location }}</p>
            <img :src="qrUrl" alt="QR detail barang" class="mx-auto mt-5 h-[180px] w-[180px]" />
            <p class="mt-4 text-xl font-extrabold">SIPB-{{ item.id }}</p>
            <p class="mt-2 text-xs">Ditemukan: {{ formatDate(item.found_at) }}</p>
            <p class="mt-2 text-xs">QR berlaku sampai: {{ formatDate(expiresAt) }}</p>
            <p class="mt-4 text-xs leading-5">
                Scan QR untuk mengisi klaim barang. Simpan label ini bersama barang di pos lost and found.
            </p>
        </section>
    </main>
</template>
