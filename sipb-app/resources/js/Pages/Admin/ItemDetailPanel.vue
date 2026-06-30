<script setup>
import {
    AlignLeft,
    AlertTriangle,
    Archive,
    CalendarDays,
    CheckCircle2,
    CheckSquare,
    ClipboardCheck,
    Clock,
    Download,
    Edit3,
    Eye,
    FileText,
    Info,
    MapPin,
    Save,
    Trash2,
    User,
    X,
} from "@lucide/vue";
import { ref } from "vue";
import ImagePreviewModal from "../../Shared/ImagePreviewModal.vue";
import { formatDate, statusClass, statusLabel } from "../../Shared/status";

const props = defineProps({
    item: { type: Object, required: true },
});

const emit = defineEmits(["close", "edit", "status-change", "delete"]);

const fallbackImage = "/assets/logo-uym.png";
const previewImage = ref(null);

function firstAuditAt(item, status) {
    return item.audits?.find((a) => a.to_status === status)?.created_at ?? null;
}

function timelineSteps(item) {
    const steps = [
        {
            title: "Laporan masuk",
            caption: "Data barang diterima sistem.",
            time: item.created_at ?? item.found_at,
            active: true,
        },
        {
            title: "Dipublish",
            caption: "Barang tampil di halaman cari publik.",
            time: item.published_at ?? firstAuditAt(item, "tersedia"),
            active:
                Boolean(item.published_at) ||
                ["tersedia", "sudah_diambil"].includes(item.status),
        },
    ];

    if (item.is_expired) {
        steps.push({
            title: "Kadaluarsa",
            caption: "Barang melewati masa tampil publik.",
            time: firstAuditAt(item, "kadaluarsa"),
            active: true,
            warning: true,
        });
    }

    return steps;
}

function useFallbackImage(event) {
    event.target.src = fallbackImage;
}

function openImagePreview(item) {
    previewImage.value = {
        src: item.photo_url,
        alt: item.name,
        title: item.name,
    };
}

function closeImagePreview() {
    previewImage.value = null;
}

function closePanel() {
    emit("close");
}

function emitEdit(item) {
    emit("edit", item);
}

function emitStatusChange(item, status) {
    emit("status-change", item, status);
}

function emitDelete(item) {
    emit("delete", item);
    closePanel();
}
</script>

<template>
    <div
        class="fixed inset-0 z-[60] bg-[#1a2134]/40 backdrop-blur-[1px]"
        @click.self="closePanel"
    >
        <section
            class="ml-auto flex h-full w-full max-w-[760px] flex-col bg-white shadow-[-24px_0_70px_rgba(26,33,52,0.24)]"
        >
            <div
                class="shrink-0 flex items-start justify-between gap-4 border-b border-[#e6e9ed] px-5 py-4"
            >
                <div>
                    <h2 class="sipb-section-title flex items-center gap-2">
                        <Eye class="h-4 w-4 text-[#2737c9]" />
                        Detail laporan
                    </h2>
                    <p class="sipb-muted mt-1 text-sm">
                        Periksa informasi sebelum publish atau tandai selesai.
                    </p>
                </div>
                <button
                    type="button"
                    class="sipb-icon-button"
                    title="Tutup"
                    @click="closePanel"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50/50 p-5">
                <div class="mx-auto max-w-5xl">
                    <div class="flex flex-col gap-6 xl:flex-row">
                        <!-- Left Column: Image & Timeline -->
                        <div
                            class="flex w-full flex-col gap-5 xl:w-[320px] shrink-0"
                        >
                            <!-- Image Preview -->
                            <div
                                class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                            >
                                <button
                                    type="button"
                                    class="group relative block aspect-[4/3] w-full overflow-hidden text-left"
                                    title="Lihat foto resolusi penuh"
                                    @click="openImagePreview(item)"
                                >
                                    <div
                                        class="absolute inset-0 bg-black/5 opacity-0 transition-opacity group-hover:opacity-100 z-10"
                                    ></div>
                                    <img
                                        :src="item.photo_url"
                                        :alt="item.name"
                                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        @error="useFallbackImage"
                                    />
                                    <div
                                        class="absolute bottom-2 right-2 rounded bg-black/60 px-2 py-1 text-[10px] font-medium text-white backdrop-blur-sm z-20 flex items-center gap-1"
                                    >
                                        <Eye class="w-3 h-3" />
                                        Perbesar
                                    </div>
                                </button>
                            </div>

                            <!-- Timeline -->
                            <div
                                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                            >
                                <div
                                    class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-3"
                                >
                                    <Clock class="h-4 w-4 text-[#2737c9]" />
                                    <h4 class="font-bold text-gray-800">
                                        Timeline Laporan
                                    </h4>
                                </div>
                                <div class="space-y-4">
                                    <div
                                        v-for="(
                                            step, index
                                        ) in timelineSteps(item)"
                                        :key="step.title"
                                        class="relative flex gap-3"
                                    >
                                        <div
                                            v-if="
                                                index !==
                                                timelineSteps(item).length - 1
                                            "
                                            class="absolute left-2.5 top-6 bottom-[-16px] w-[2px] bg-gray-100"
                                        ></div>

                                        <span
                                            :class="[
                                                'relative z-10 mt-1 grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 bg-white',
                                                step.active
                                                    ? step.warning
                                                        ? 'border-[#feae37] text-[#feae37]'
                                                        : 'border-[#2737c9] text-[#2737c9]'
                                                    : 'border-gray-200 text-gray-200',
                                            ]"
                                        >
                                            <span
                                                v-if="step.active"
                                                :class="[
                                                    'h-2 w-2 rounded-full',
                                                    step.warning
                                                        ? 'bg-[#feae37]'
                                                        : 'bg-[#2737c9]',
                                                ]"
                                            ></span>
                                        </span>
                                        <div class="min-w-0 pb-1">
                                            <p
                                                :class="[
                                                    'text-sm font-bold',
                                                    step.active
                                                        ? 'text-gray-900'
                                                        : 'text-gray-400',
                                                ]"
                                            >
                                                {{ step.title }}
                                            </p>
                                            <p
                                                class="mt-0.5 text-xs text-gray-500"
                                            >
                                                {{ step.caption }}
                                            </p>
                                            <p
                                                v-if="step.time"
                                                class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-[#2737c9]"
                                            >
                                                <CalendarDays
                                                    class="h-3 w-3"
                                                />
                                                {{ formatDate(step.time) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Details -->
                        <div class="min-w-0 flex-1 space-y-5">
                            <!-- Header & Badges -->
                            <div
                                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                            >
                                <div
                                    class="mb-3 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 whitespace-nowrap rounded-md border px-2.5 py-1 text-xs font-bold',
                                            statusClass(item.status),
                                        ]"
                                    >
                                        <Info class="h-3.5 w-3.5" />
                                        {{ statusLabel(item.status) }}
                                    </span>
                                    <span
                                        v-if="item.is_expired"
                                        class="inline-flex items-center gap-1 rounded-md bg-[#d93c3c] px-2.5 py-1 text-xs font-bold text-white shadow-sm"
                                    >
                                        <AlertTriangle class="h-3.5 w-3.5" />
                                        Lewat 30 hari
                                    </span>
                                </div>
                                <h3
                                    class="text-2xl font-extrabold text-gray-900 sm:text-3xl"
                                >
                                    {{ item.name }}
                                </h3>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-3 text-sm font-medium text-gray-500"
                                >
                                    <span
                                        class="flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1"
                                    >
                                        <Archive class="h-3.5 w-3.5" />
                                        {{ item.category }}
                                    </span>
                                    <span
                                        class="flex items-center gap-1.5 text-gray-600"
                                    >
                                        <MapPin class="h-4 w-4 text-gray-400" />
                                        Ditemukan di {{ item.location }}
                                    </span>
                                </div>
                            </div>

                            <!-- Key Information Grid -->
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div
                                    class="flex flex-col justify-center rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                                >
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500"
                                    >
                                        <CalendarDays
                                            class="h-4 w-4 text-[#2737c9]"
                                        />
                                        Tanggal Laporan Masuk
                                    </div>
                                    <div
                                        class="mt-2 text-sm font-semibold text-gray-900"
                                    >
                                        {{ formatDate(item.found_at) }}
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col justify-center rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                                >
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500"
                                    >
                                        <User class="h-4 w-4 text-[#2737c9]" />
                                        Data Pelapor
                                    </div>
                                    <div
                                        class="mt-2 text-sm font-semibold text-gray-900"
                                    >
                                        {{ item.finder_name || "Hamba Allah" }}
                                        <span
                                            v-if="item.finder_nim"
                                            class="ml-1 text-gray-400 font-normal"
                                            >({{ item.finder_nim }})</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col justify-center rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                                >
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500"
                                    >
                                        <Archive
                                            class="h-4 w-4 text-[#2737c9]"
                                        />
                                        Lokasi Penyimpanan
                                    </div>
                                    <div
                                        class="mt-2 text-sm font-semibold text-gray-900"
                                    >
                                        {{
                                            item.storage_location ||
                                            "Belum ditentukan"
                                        }}
                                    </div>
                                </div>
                                <div
                                    v-if="item.claimant_name"
                                    class="flex flex-col justify-center rounded-xl border border-green-100 bg-green-50/50 p-5 shadow-sm"
                                >
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-green-700"
                                    >
                                        <CheckSquare
                                            class="h-4 w-4 text-green-600"
                                        />
                                        Data Pengambil
                                    </div>
                                    <div
                                        class="mt-2 text-sm font-semibold text-green-900"
                                    >
                                        {{ item.claimant_name }}
                                        <span
                                            v-if="item.claimant_nim"
                                            class="ml-1 text-green-700 font-normal"
                                            >({{ item.claimant_nim }})</span
                                        >
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="flex flex-col justify-center rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                                >
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500"
                                    >
                                        <ClipboardCheck
                                            class="h-4 w-4 text-[#2737c9]"
                                        />
                                        Tanggal Publikasi
                                    </div>
                                    <div
                                        class="mt-2 text-sm font-semibold text-gray-900"
                                    >
                                        {{
                                            item.published_at
                                                ? formatDate(item.published_at)
                                                : "Belum dipublikasikan"
                                        }}
                                    </div>
                                </div>
                            </div>

                            <!-- Long Text Sections -->
                            <div
                                class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm space-y-5"
                            >
                                <div>
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100 pb-2"
                                    >
                                        <AlignLeft
                                            class="h-4 w-4 text-gray-400"
                                        />
                                        Deskripsi Lengkap
                                    </div>
                                    <p
                                        class="mt-3 text-sm leading-relaxed text-gray-700 whitespace-pre-wrap"
                                    >
                                        {{ item.description }}
                                    </p>
                                </div>

                                <div v-if="item.admin_notes" class="pt-2">
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100 pb-2"
                                    >
                                        <FileText
                                            class="h-4 w-4 text-gray-400"
                                        />
                                        Catatan Admin
                                    </div>
                                    <p
                                        class="mt-3 text-sm leading-relaxed text-gray-700 whitespace-pre-wrap"
                                    >
                                        {{ item.admin_notes }}
                                    </p>
                                </div>

                                <div v-if="item.validation_notes" class="pt-2">
                                    <div
                                        class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500 border-b border-gray-100 pb-2"
                                    >
                                        <AlertTriangle
                                            class="h-4 w-4 text-gray-400"
                                        />
                                        Catatan Validasi
                                    </div>
                                    <p
                                        class="mt-3 text-sm leading-relaxed text-gray-700 whitespace-pre-wrap"
                                    >
                                        {{ item.validation_notes }}
                                    </p>
                                </div>
                            </div>

                            <!-- Duplicate Warning -->
                            <div
                                v-if="item.duplicate_candidates?.length"
                                class="rounded-xl border border-orange-200 bg-orange-50 p-5 shadow-sm"
                            >
                                <div
                                    class="flex items-center gap-2 text-sm font-bold text-orange-800"
                                >
                                    <AlertTriangle class="h-5 w-5" />
                                    Kemungkinan Duplikat Terdeteksi
                                </div>
                                <p class="mt-1 text-xs text-orange-700">
                                    Barang ini mirip dengan laporan berikut:
                                </p>
                                <div class="mt-3 space-y-2">
                                    <button
                                        v-for="candidate in item.duplicate_candidates"
                                        :key="candidate.id"
                                        type="button"
                                        class="block w-full rounded-lg bg-white border border-orange-100 px-4 py-3 text-left transition hover:border-orange-300 hover:shadow-sm"
                                    >
                                        <span
                                            class="block text-sm font-extrabold text-gray-900"
                                            >#{{ candidate.id }} -
                                            {{ candidate.name }}</span
                                        >
                                        <span
                                            class="mt-1 block flex flex-wrap items-center gap-2 text-xs text-gray-500"
                                        >
                                            <span
                                                class="font-medium text-gray-700"
                                                >{{
                                                    statusLabel(candidate.status)
                                                }}</span
                                            >
                                            <span>•</span>
                                            <span>{{
                                                candidate.location
                                            }}</span>
                                            <span>•</span>
                                            <span>{{
                                                formatDate(candidate.created_at)
                                            }}</span>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="shrink-0 flex flex-col-reverse gap-2 border-t border-[#e6e9ed] bg-white px-5 py-4 sm:flex-row sm:justify-end"
            >
                <button
                    type="button"
                    class="sipb-button-secondary"
                    @click="closePanel"
                >
                    Tutup
                </button>
                <button
                    type="button"
                    class="sipb-button-secondary inline-flex items-center justify-center gap-2 border-[#d93c3c] text-[#d93c3c] hover:bg-[#d93c3c] hover:text-white"
                    @click="emitDelete(item)"
                >
                    <Trash2 class="h-4 w-4" />
                    Hapus
                </button>
                <button
                    type="button"
                    class="sipb-button-secondary inline-flex items-center justify-center gap-2"
                    @click="emitEdit(item)"
                >
                    <Edit3 class="h-4 w-4" />
                    Edit
                </button>
                <button
                    v-if="item.status !== 'tersedia'"
                    type="button"
                    class="sipb-button-primary inline-flex items-center justify-center gap-2 bg-[#00a676] hover:bg-[#008a67]"
                    @click="emitStatusChange(item, 'tersedia')"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    Publish
                </button>
                <button
                    v-if="item.status !== 'sudah_diambil'"
                    type="button"
                    class="sipb-button-primary inline-flex items-center justify-center gap-2"
                    @click="emitStatusChange(item, 'sudah_diambil')"
                >
                    <Save class="h-4 w-4" />
                    Tandai selesai
                </button>
                <a
                    v-if="item.status === 'sudah_diambil'"
                    :href="`/admin/barang/${item.id}/tanda-terima`"
                    target="_blank"
                    class="sipb-button-secondary inline-flex items-center justify-center gap-2"
                >
                    <Download class="h-4 w-4" />
                    Tanda terima
                </a>
            </div>
        </section>
        <ImagePreviewModal
            :image="previewImage"
            @close="closeImagePreview"
        />
    </div>
</template>
