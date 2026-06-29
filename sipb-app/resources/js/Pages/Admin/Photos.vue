<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { Image as ImageIcon, Trash2, Upload, X } from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import AppLayout from "../../Shared/AppLayout.vue";
import Swal from "sweetalert2";

const props = defineProps({
    photos: Array,
});

const page = usePage();
const photos = ref(props.photos ?? []);
const uploading = ref(false);
const pageLoading = ref(!props.photos?.length);
const uploadInput = ref(null);
let removePageStartListener = null;
let removePageFinishListener = null;

const csrfToken = computed(
    () =>
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? "",
);

function triggerUpload() {
    uploadInput.value?.click();
}

function handleFileSelect(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
        Swal.fire({ icon: "error", title: "Gagal", text: "File harus berupa gambar." });
        return;
    }

    if (file.size > 4 * 1024 * 1024) {
        Swal.fire({ icon: "error", title: "Gagal", text: "Ukuran foto maksimal 4 MB." });
        return;
    }

    uploadFile(file);
}

async function uploadFile(file) {
    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append("photo", file);

        const response = await fetch("/admin/upload-photo", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken.value,
                Accept: "application/json",
            },
            body: formData,
        });

        if (!response.ok) {
            const err = await response.json().catch(() => null);
            throw new Error(err?.message ?? "Gagal mengupload foto.");
        }

        const result = await response.json();
        photos.value.unshift(result);
    } catch (e) {
        Swal.fire({ icon: "error", title: "Gagal", text: e.message });
    } finally {
        uploading.value = false;
        if (uploadInput.value) uploadInput.value.value = "";
    }
}

function hapusFoto(photoId) {
    Swal.fire({
        icon: "warning",
        title: "Hapus foto?",
        text: "Foto yang dihapus tidak bisa dikembalikan.",
        showCancelButton: true,
        confirmButtonText: "Ya, hapus",
        cancelButtonText: "Batal",
        confirmButtonColor: "#d93c3c",
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(`/admin/uploaded-photos/${photoId}`, {
            preserveScroll: true,
            onSuccess: () => {
                photos.value = photos.value.filter((p) => p.id !== photoId);
            },
        });
    });
}

onMounted(() => {
    pageLoading.value = false;
    removePageStartListener = router.on("start", (visit) => {
        pageLoading.value = true;
    });
    removePageFinishListener = router.on("finish", () => {
        pageLoading.value = false;
    });
});

onBeforeUnmount(() => {
    removePageStartListener?.();
    removePageFinishListener?.();
});
</script>

<template>
    <AppLayout title="Upload Foto" :admin="true">
        <div class="mx-auto max-w-4xl">
            <div
                class="mb-6 flex flex-col gap-4 rounded-xl bg-white p-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-lg font-extrabold text-[#071735]">
                        Upload Foto Barang
                    </h2>
                    <p class="mt-1 text-sm text-[#747a8b]">
                        Ambil foto barang menggunakan kamera HP, lalu upload ke
                        sini. Foto akan tersedia saat membuat laporan barang
                        baru.
                    </p>
                </div>

                <button
                    type="button"
                    :disabled="uploading"
                    class="flex shrink-0 items-center gap-2 rounded-lg bg-[#2737c9] px-5 py-2.5 text-sm font-bold text-white shadow-[0_4px_12px_rgba(39,55,201,0.25)] transition-all hover:bg-[#202da8] disabled:opacity-50"
                    @click="triggerUpload"
                >
                    <Upload class="h-4 w-4" />
                    {{ uploading ? "Mengupload..." : "Ambil / Pilih Foto" }}
                </button>

                <input
                    ref="uploadInput"
                    type="file"
                    accept="image/*"
                    capture="environment"
                    class="hidden"
                    @change="handleFileSelect"
                />
            </div>

            <div
                v-if="pageLoading"
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4"
            >
                <div
                    v-for="i in 8"
                    :key="'sk-' + i"
                    class="overflow-hidden rounded-xl bg-white"
                >
                    <div class="aspect-square sipb-skeleton rounded-none"></div>
                    <div class="px-3 pb-3 pt-2">
                        <span class="sipb-skeleton h-3 w-24"></span>
                    </div>
                </div>
            </div>

            <div
                v-else-if="photos.length === 0"
                class="flex flex-col items-center justify-center rounded-xl bg-white px-6 py-16"
            >
                <div
                    class="mb-4 grid h-16 w-16 place-items-center rounded-full bg-[#f6f7fa]"
                >
                    <ImageIcon class="h-7 w-7 text-[#9da3b1]" />
                </div>
                <p class="text-sm font-semibold text-[#747a8b]">
                    Belum ada foto yang diupload
                </p>
                <p class="mt-1 text-xs text-[#9da3b1]">
                    Klik tombol di atas untuk mulai mengambil foto.
                </p>
            </div>

            <div
                v-else
                class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4"
            >
                <div
                    v-for="photo in photos"
                    :key="photo.id"
                    class="group relative overflow-hidden rounded-xl bg-white"
                >
                    <div class="aspect-square">
                        <img
                            :src="photo.photo_data"
                            alt="Uploaded photo"
                            class="h-full w-full object-cover"
                        />
                    </div>

                    <button
                        type="button"
                        title="Hapus foto"
                        class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-600/80"
                        @click="hapusFoto(photo.id)"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>

                    <div
                        class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/50 to-transparent px-3 pb-2 pt-6"
                    >
                        <p class="text-[11px] font-semibold text-white/90">
                            {{ new Date(photo.created_at).toLocaleDateString("id-ID", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" }) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
