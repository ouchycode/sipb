<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../Shared/AppLayout.vue';

const props = defineProps({
    status: Number,
    isAdmin: Boolean,
});

const title = computed(() => {
    return {
        503: 'Layanan Tidak Tersedia',
        500: 'Kesalahan Server',
        404: 'Uppss!!! Alamat yang Anda tuju tidak ditemukan',
        403: 'Akses Ditolak',
    }[props.status] || 'Terjadi Kesalahan';
});

const description = computed(() => {
    return {
        503: 'Maaf, kami sedang melakukan pemeliharaan. Silakan periksa kembali nanti.',
        500: 'Wah, sepertinya ada yang salah di server kami.',
        404: 'Mohon periksa kembali URL yang Anda ketikkan atau mohon hubungi administrator',
        403: 'Maaf, Anda tidak memiliki akses ke halaman ini.',
    }[props.status] || 'Terjadi kesalahan sistem.';
});

const imageSrc = computed(() => {
    if (props.status === 404) {
        return '/assets/error-404.3f4f7b2.png';
    }
    return null; // Bisa ditambahkan gambar error lain jika ada
});
</script>

<template>
    <Head :title="`Error ${status}`" />
    <AppLayout :title="`Error ${status}`" :show-page-header="false" :admin="isAdmin">
        <div class="flex min-h-[calc(100vh-100px)] flex-col items-center justify-center px-4 py-10 text-center">
            
            <img
                v-if="imageSrc"
                :src="imageSrc"
                alt="404 Not Found"
                class="mb-6 w-full max-w-[480px] object-contain drop-shadow-sm"
            />
            <div v-else class="mb-8 text-[120px] font-extrabold leading-none text-[#2737c9]">
                {{ status }}
            </div>

            <h1 class="mb-2 text-xl font-extrabold text-[#1a2134] sm:text-2xl">
                {{ title }}
            </h1>
            <p class="mb-8 max-w-lg text-sm font-medium text-[#747a8b]">
                {{ description }}
            </p>

            <Link
                :href="isAdmin ? '/admin' : '/'"
                class="sipb-button-primary inline-flex h-11 items-center justify-center px-6"
            >
                {{ isAdmin ? 'Kembali ke Dashboard' : 'Kembali ke Beranda' }}
            </Link>

        </div>
    </AppLayout>
</template>
