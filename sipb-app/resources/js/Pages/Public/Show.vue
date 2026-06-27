<script setup>
import { Link, Head } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Check, ClipboardCheck, Copy, Info, MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '../../Shared/AppLayout.vue';
import ImagePreviewModal from '../../Shared/ImagePreviewModal.vue';
import { formatDate, statusClass, statusLabel } from '../../Shared/status';

const props = defineProps({
    item: Object,
});

const previewImage = ref(null);

function openImagePreview() {
    previewImage.value = {
        src: props.item.photo_url,
        alt: props.item.name,
        title: props.item.name,
    };
}

function closeImagePreview() {
    previewImage.value = null;
}
</script>

<template>
    <Head>
        <title>{{ item.name }} - SIPB UYM</title>
        <meta head-key="description" name="description" :content="`Barang temuan: ${item.name} di ${item.location}. Lihat detailnya di SIPB Universitas Yatsi Madani.`" />
        <meta head-key="og:title" property="og:title" :content="`Ditemukan: ${item.name}`" />
        <meta head-key="og:description" property="og:description" :content="item.description" />
        <meta head-key="og:image" property="og:image" :content="item.photo_url" />
    </Head>
    <AppLayout title="Detail laporan" :show-page-header="false">
        <Link href="/cari" class="mb-4 inline-flex items-center gap-2 text-sm font-semibold text-[#747a8b] hover:text-[#2737c9]">
            <ArrowLeft class="h-4 w-4" />
            Kembali ke pencarian
        </Link>

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
            <div class="sipb-panel sipb-interactive-card overflow-hidden">
                <button type="button" class="sipb-card-media group block w-full text-left" title="Lihat foto" @click="openImagePreview">
                    <img :src="item.photo_url" :alt="item.name" class="aspect-[16/10] w-full object-cover transition-transform duration-200 group-hover:scale-105" />
                </button>
                <div class="space-y-4 p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h1 class="sipb-page-title">{{ item.name }}</h1>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="sipb-chip">{{ item.category }}</span>
                                <span class="sipb-chip">Temuan</span>
                            </div>
                        </div>
                        <span :class="['w-max rounded-full border px-3 py-1 text-xs font-medium', statusClass(item.status)]">{{ statusLabel(item.status) }}</span>
                    </div>
                    <p class="sipb-muted text-sm leading-6">{{ item.description }}</p>

                    <div class="grid gap-3 border-t border-[#e6e9ed] pt-4 text-sm font-semibold text-[#1a2134] sm:grid-cols-2">
                        <div class="flex items-center gap-2"><MapPin class="h-4 w-4 text-[#747a8b]" /> {{ item.location }}</div>
                        <div class="flex items-center gap-2"><CalendarDays class="h-4 w-4 text-[#747a8b]" /> {{ formatDate(item.found_at) }}</div>
                    </div>
                </div>
            </div>

            <aside class="sipb-accent-panel h-max p-5 lg:sticky lg:top-6">
                <div class="mb-5 rounded-md bg-white p-4 shadow-[0_10px_28px_rgba(203,213,225,0.24)]">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-[#2737c9] text-white">
                            <ClipboardCheck class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="font-extrabold text-[#1a2134]">Ingin mengambil barang ini?</p>
                            <p class="mt-1 text-sm leading-6 text-[#64748b]">
                                Datang ke admin layanan lost and found, tunjukkan kode barang, lalu admin memvalidasi bukti kepemilikan langsung di tempat.
                            </p>
                        </div>
                    </div>

                </div>
                <div class="flex items-center gap-2 text-sm font-bold text-[#1a2134]">
                    <Info class="h-4 w-4" />
                    Instruksi pengambilan
                </div>
                <p class="sipb-muted mt-3 text-sm leading-6">
                    Jika barang ini milik Anda, datang ke admin dengan identitas kampus dan bukti kepemilikan. Jika cocok, admin langsung menandai barang sebagai sudah diambil.
                </p>
                <p class="sipb-muted mt-3 text-sm leading-6">
                    Halaman publik tidak menampilkan kontak pribadi penemu barang.
                </p>

                <div class="mt-5 rounded-md bg-[#fff7ed] p-4 text-sm font-semibold leading-6 text-[#8a5b12]">
                    Pengambilan barang hanya diproses langsung di pos layanan agar admin bisa mencocokkan identitas dan bukti kepemilikan.
                </div>
            </aside>
        </section>
        <ImagePreviewModal
            v-if="previewImage"
            :src="previewImage.src"
            :alt="previewImage.alt"
            :title="previewImage.title"
            @close="closeImagePreview"
        />
    </AppLayout>
</template>
