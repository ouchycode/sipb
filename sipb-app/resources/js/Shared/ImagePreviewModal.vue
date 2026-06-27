<script setup>
import { X } from "@lucide/vue";

defineProps({
    src: {
        type: String,
        required: true,
    },
    alt: {
        type: String,
        default: "Preview foto barang",
    },
    title: {
        type: String,
        default: "Foto barang",
    },
});

defineEmits(["close"]);
</script>

<template>
    <div class="fixed inset-0 z-[70] grid place-items-center bg-[#1a2134]/72 px-4 py-6 backdrop-blur-sm" @click.self="$emit('close')">
        <section class="w-full max-w-4xl overflow-hidden rounded-md bg-white shadow-[0_24px_80px_rgba(0,0,0,0.32)]">
            <div class="flex items-center justify-between gap-4 border-b border-[#e6e9ed] px-4 py-3">
                <div class="min-w-0">
                    <h2 class="truncate text-base font-extrabold text-[#1a2134]">{{ title }}</h2>
                    <p class="mt-0.5 text-xs font-semibold text-[#747a8b]">Klik area luar untuk menutup preview.</p>
                </div>
                <button type="button" class="sipb-icon-button shrink-0" title="Tutup preview" @click="$emit('close')">
                    <X class="h-4 w-4" />
                </button>
            </div>
            <div class="max-h-[78vh] overflow-auto bg-[#f6f7fa] p-3">
                <img :src="src" :alt="alt" class="mx-auto max-h-[72vh] w-auto max-w-full rounded-md object-contain" />
            </div>
        </section>
    </div>
</template>
