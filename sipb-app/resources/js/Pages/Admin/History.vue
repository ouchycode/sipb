<script setup>
import { Link, router } from "@inertiajs/vue3";
import {
    Archive,
    CalendarDays,
    ClipboardList,
    Download,
    Filter,
    History,
    MapPin,
    Search,
} from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import ActiveFilters from "../../Shared/ActiveFilters.vue";
import AppLayout from "../../Shared/AppLayout.vue";
import FilterDrawer from "../../Shared/FilterDrawer.vue";
import ImagePreviewModal from "../../Shared/ImagePreviewModal.vue";
import Pagination from "../../Shared/Pagination.vue";
import SearchToolbar from "../../Shared/SearchToolbar.vue";
import { formatDate, statusClass, statusLabel } from "../../Shared/status";

const props = defineProps({
    items: Object,
    filters: Object,
    categories: Array,
    locations: Array,
});

const filters = reactive({
    q: props.filters.q ?? "",
    category: props.filters.category ?? "",
    location: props.filters.location ?? "",
    per_page: props.filters.per_page ?? 10,
});

const showAdvancedFilters = ref(false);

const fallbackImage = "/assets/logo-uym.png";
const itemRows = computed(() => props.items.data ?? props.items);
const itemTotal = computed(() => props.items.total ?? itemRows.value.length);
const skeletonRows = computed(() =>
    Math.min(Number(filters.per_page) || 10, 10),
);
const isDenseTable = computed(() => Number(filters.per_page) >= 20);
const isVeryDenseTable = computed(() => Number(filters.per_page) >= 50);
const pageLoading = ref(false);
const previewImage = ref(null);
let removePageStartListener = null;
let removePageFinishListener = null;

function applyFilters() {
    router.get("/admin/history", filters, {
        preserveState: true,
        replace: true,
    });
}

function resetFilters() {
    filters.q = "";
    filters.category = "";
    filters.location = "";
    applyFilters();
}

const activeFilters = computed(() =>
    [
        filters.q ? { key: "q", label: `Keyword: ${filters.q}` } : null,
        filters.category ? { key: "category", label: `Kategori: ${filters.category}` } : null,
        filters.location ? { key: "location", label: `Lokasi: ${filters.location}` } : null,
    ].filter(Boolean),
);

function clearFilter(key) {
    filters[key] = "";
    applyFilters();
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

function itemStatusLabel(item) {
    return item.claimed_at ? statusLabel(item.status) : 'Kadaluarsa';
}

function itemStatusClass(item) {
    return item.claimed_at ? statusClass(item.status) : statusClass('kadaluarsa');
}

onMounted(() => {
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
    <AppLayout title="History barang" admin>
        <section
            class="mb-7 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex-1">
                <p class="text-sm font-semibold text-[#747a8b]">
                    Arsip barang yang sudah selesai, lengkap dengan data
                    pengambil dan catatan validasi.
                </p>
            </div>
        </section>

        <section class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
            <div class="sipb-panel p-5">
                <div class="flex items-start gap-4">
                    <div
                        class="grid h-11 w-11 shrink-0 place-items-center rounded-md bg-[#2737c9] text-white"
                    >
                        <History class="h-5 w-5" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-[#1a2134]">
                            {{ itemTotal }} barang selesai
                        </p>
                        <p class="mt-1 text-sm leading-6 text-[#747a8b]">
                            Data di halaman ini otomatis berasal dari barang
                            yang ditandai sudah diambil oleh admin. Identitas
                            pengambil wajib dicek di kolom pengambil.
                        </p>
                    </div>
                </div>
            </div>
            <div class="sipb-panel p-5">
                <p class="text-xs font-bold uppercase text-[#747a8b]">
                    Status arsip
                </p>
                <div class="mt-4 flex items-center gap-3">
                    <Archive class="h-5 w-5 text-[#00bf8e]" />
                    <span
                        class="rounded-full border px-2.5 py-1 text-xs font-medium"
                        :class="statusClass('sudah_diambil')"
                    >
                        {{ statusLabel("sudah_diambil") }}
                    </span>
                </div>
            </div>
        </section>

        <SearchToolbar
            v-model="filters.q"
            @search="applyFilters"
        >
            <template #actions>
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md border border-[#00bf8e] px-4 py-1.5 text-sm font-bold text-[#00bf8e] transition-colors hover:bg-[#00bf8e] hover:text-white"
                    @click="showAdvancedFilters = !showAdvancedFilters"
                >
                    <Filter class="h-4 w-4" /> Filter
                </button>
            </template>
        </SearchToolbar>

        <ActiveFilters :filters="activeFilters" @remove="clearFilter" />

        <FilterDrawer
            :show="showAdvancedFilters"
            @close="showAdvancedFilters = false"
            @apply="applyFilters"
            @reset="resetFilters"
        >
            <label class="block">
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]">Kategori</span>
                <select
                    v-model="filters.category"
                    class="w-full rounded-md border border-[#e2e8f0] px-3 py-2.5 text-sm focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]"
                >
                    <option value="">Semua</option>
                    <option
                        v-for="category in categories"
                        :key="category"
                        :value="category"
                    >
                        {{ category }}
                    </option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]">Lokasi</span>
                <select
                    v-model="filters.location"
                    class="w-full rounded-md border border-[#e2e8f0] px-3 py-2.5 text-sm focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]"
                >
                    <option value="">Semua</option>
                    <option
                        v-for="location in locations"
                        :key="location"
                        :value="location"
                    >
                        {{ location }}
                    </option>
                </select>
            </label>
        </FilterDrawer>

        <section class="overflow-hidden">
            <div class="grid gap-3 p-4 md:hidden">
                <template v-if="pageLoading">
                    <article
                        v-for="index in skeletonRows"
                        :key="`history-card-skeleton-${index}`"
                        class="rounded-md sipb-panel p-4"
                    >
                        <div class="flex gap-3">
                            <span
                                class="sipb-skeleton h-20 w-24 shrink-0"
                            ></span>
                            <div class="min-w-0 flex-1 space-y-2">
                                <span class="sipb-skeleton h-4 w-4/5"></span>
                                <span class="sipb-skeleton h-3 w-2/3"></span>
                                <span class="sipb-skeleton h-7 w-24"></span>
                            </div>
                        </div>
                        <div class="mt-4 space-y-2">
                            <span class="sipb-skeleton h-3 w-3/4"></span>
                            <span class="sipb-skeleton h-3 w-2/3"></span>
                            <span class="sipb-skeleton h-3 w-1/2"></span>
                        </div>
                    </article>
                </template>
                <div
                    v-else-if="itemRows.length === 0"
                    class="rounded-md bg-[#f6f7fa] p-5 text-center text-sm font-medium text-[#747a8b]"
                >
                    Belum ada history barang selesai.
                </div>
                <template v-else>
                    <article
                        v-for="item in itemRows"
                        :key="`history-card-${item.id}`"
                        class="rounded-md sipb-panel p-4"
                    >
                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="group h-20 w-24 shrink-0 overflow-hidden rounded-md bg-[#f6f7fa]"
                                title="Lihat foto"
                                @click="openImagePreview(item)"
                            >
                                <img
                                    :src="item.photo_url"
                                    :alt="item.name"
                                    class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                    @error="useFallbackImage"
                                />
                            </button>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate font-extrabold text-[#1a2134]"
                                >
                                    {{ item.name }}
                                </p>
                                <p
                                    class="mt-1 text-xs font-semibold text-[#747a8b]"
                                >
                                    {{ item.category }} - {{ item.location }}
                                </p>
                                <span
                                    :class="[
                                        'mt-2 inline-flex rounded-md border px-2.5 py-1 text-xs font-medium',
                                        itemStatusClass(item),
                                    ]"
                                >
                                    {{ itemStatusLabel(item) }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="mt-4 grid gap-2 text-xs font-medium text-[#747a8b]"
                        >
                            <p>
                                <span class="font-bold text-[#1a2134]"
                                    >Ditemukan:</span
                                >
                                {{ formatDate(item.found_at) }}
                            </p>
                            <p>
                                <span class="font-bold text-[#1a2134]"
                                    >Diambil:</span
                                >
                                {{ formatDate(item.claimed_at) }}
                            </p>
                            <p>
                                <span class="font-bold text-[#1a2134]"
                                    >Pengambil:</span
                                >
                                {{ item.claimant_name || "-" }}
                                <span v-if="item.claimant_nim"
                                    >({{ item.claimant_nim }})</span
                                >
                            </p>
                            <p v-if="item.validation_notes">
                                <span class="font-bold text-[#1a2134]"
                                    >Catatan:</span
                                >
                                {{ item.validation_notes }}
                            </p>
                            <p>
                                <span class="font-bold text-[#1a2134]"
                                    >Admin:</span
                                >
                                {{ item.manager?.name || "-" }}
                            </p>
                            <a
                                :href="`/admin/barang/${item.id}/tanda-terima`"
                                target="_blank"
                                class="mt-3 inline-flex items-center gap-1.5 rounded-md border border-[#2737c9] px-3 py-1.5 text-xs font-bold text-[#2737c9] transition-colors hover:bg-[#2737c9] hover:text-white"
                            >
                                <Download class="h-3.5 w-3.5" />
                                Tanda terima
                            </a>
                        </div>
                    </article>
                </template>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[1320px] w-full text-sm">
                    <colgroup>
                        <col class="w-[50px]" />
                        <col class="w-[240px]" />
                        <col class="w-[160px]" />
                        <col class="w-[140px]" />
                        <col class="w-[140px]" />
                        <col class="w-[210px]" />
                        <col class="w-[130px]" />
                        <col class="w-[130px]" />
                        <col class="w-[120px]" />
                    </colgroup>
                    <thead class="border-b border-[#e2e8f0] text-left">
                        <tr>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                No
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Barang
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Lokasi
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Ditemukan
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Diambil
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Pengambil
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Admin
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Status
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="pageLoading">
                            <tr
                                v-for="index in skeletonRows"
                                :key="`history-row-skeleton-${index}`"
                                class="align-middle"
                            >
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <div class="flex items-center gap-3">
                                        <span
                                            :class="['sipb-skeleton shrink-0', isDenseTable ? 'h-9 w-11' : 'h-12 w-14']"
                                        ></span>
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <span
                                                class="sipb-skeleton h-4 w-4/5"
                                            ></span>
                                            <span
                                                class="sipb-skeleton h-3 w-2/5"
                                            ></span>
                                        </div>
                                    </div>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <span class="sipb-skeleton h-4 w-32"></span>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <span class="sipb-skeleton h-4 w-28"></span>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <span class="sipb-skeleton h-4 w-28"></span>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <div class="space-y-2">
                                        <span
                                            class="sipb-skeleton h-4 w-32"
                                        ></span>
                                        <span
                                            class="sipb-skeleton h-3 w-24"
                                        ></span>
                                    </div>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <span class="sipb-skeleton h-4 w-24"></span>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <span class="sipb-skeleton h-7 w-28"></span>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <span class="sipb-skeleton h-7 w-20"></span>
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-else-if="itemRows.length === 0"
                            class="border-b border-[#f1f5f9] bg-white"
                        >
                            <td
                                colspan="9"
                                class="px-4 py-8 text-center text-[#747a8b]"
                            >
                                Belum ada history barang selesai.
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="(item, index) in itemRows"
                            :key="item.id"
                            class="border-b border-[#f1f5f9] align-middle odd:bg-white even:bg-[#f8f9fd] hover:bg-[#f1f5f9]"
                        >
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'text-[#1a2134]',
                                ]"
                            >
                                {{
                                    (props.items.current_page - 1) *
                                        props.items.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle',
                                ]"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <button
                                        type="button"
                                        :class="[
                                            isDenseTable
                                                ? 'h-9 w-11'
                                                : 'h-12 w-14',
                                            'group shrink-0 overflow-hidden rounded-md bg-[#f6f7fa]',
                                        ]"
                                        title="Lihat foto"
                                        @click="openImagePreview(item)"
                                    >
                                        <img
                                            :src="item.photo_url"
                                            :alt="item.name"
                                            class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                            @error="useFallbackImage"
                                        />
                                    </button>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-bold text-[#1a2134]"
                                        >
                                            {{ item.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-[#747a8b]"
                                        >
                                            {{ item.category }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle text-[#747a8b]',
                                ]"
                            >
                                <span class="flex min-w-0 items-start gap-2">
                                    <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                                    <span
                                        class="min-w-0 truncate font-semibold"
                                        >{{ item.location }}</span
                                    >
                                </span>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle text-[#747a8b]',
                                ]"
                            >
                                <span class="flex min-w-0 items-start gap-2">
                                    <CalendarDays
                                        class="mt-0.5 h-4 w-4 shrink-0"
                                    />
                                    <span class="leading-5">{{
                                        formatDate(item.found_at)
                                    }}</span>
                                </span>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle font-semibold leading-5 text-[#1a2134]',
                                ]"
                            >
                                {{ formatDate(item.claimed_at) }}
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle',
                                ]"
                            >
                                <p class="truncate font-bold text-[#1a2134]">
                                    {{ item.claimant_name || "-" }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs font-semibold text-[#747a8b]"
                                >
                                    {{ item.claimant_nim || "NIM tidak diisi" }}
                                </p>
                                <p
                                    v-if="item.validation_notes"
                                    class="mt-1 line-clamp-2 text-xs leading-5 text-[#747a8b]"
                                >
                                    {{ item.validation_notes }}
                                </p>
                            </td>
                            <td class="px-4 py-3 align-middle text-[#747a8b]">
                                <p class="truncate font-semibold">
                                    {{ item.manager?.name || "-" }}
                                </p>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    :class="[
                                        'inline-flex whitespace-nowrap rounded-md border px-2.5 py-1 text-xs font-medium',
                                        itemStatusClass(item),
                                    ]"
                                >
                                    {{ itemStatusLabel(item) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <a
                                    :href="`/admin/barang/${item.id}/tanda-terima`"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-[#2737c9] px-2.5 py-1.5 text-xs font-bold text-[#2737c9] transition-colors hover:bg-[#2737c9] hover:text-white"
                                >
                                    <Download class="h-3.5 w-3.5" />
                                    Cetak
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                <Pagination
                    :meta="items"
                    @update:perPage="
                        (val) => {
                            filters.per_page = val;
                            applyFilters();
                        }
                    "
                />
            </div>
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
