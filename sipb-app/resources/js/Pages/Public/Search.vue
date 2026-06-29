<script setup>
import { Link, router, Head } from "@inertiajs/vue3";
import {
    CalendarDays,
    ClipboardCheck,
    MapPin,
    Search,
    SlidersHorizontal,
    UserRound,
    X,
} from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import AppLayout from "../../Shared/AppLayout.vue";
import Pagination from "../../Shared/Pagination.vue";
import { formatDate, statusClass, statusLabel } from "../../Shared/status";

const props = defineProps({
    items: Object,
    filters: Object,
    categories: Array,
    locations: Array,
});

const fallbackImage = "/assets/logo-uym.png";
const itemRows = computed(() => props.items.data ?? props.items);
const itemTotal = computed(() => props.items.total ?? itemRows.value.length);
const pageLoading = ref(false);
const skeletonRows = computed(() => Math.min(Number(form.per_page) || 9, 9));
const showMobileFilters = ref(false);
let removePageStartListener = null;
let removePageFinishListener = null;

const form = reactive({
    q: props.filters.q ?? "",
    category: props.filters.category ?? "",
    location: props.filters.location ?? "",
    date: props.filters.date ?? "",
    sort: props.filters.sort ?? "newest",
    per_page: props.filters.per_page ?? 9,
});

const sortOptions = [
    { value: "newest", label: "Terbaru dulu" },
    { value: "oldest", label: "Terlama dulu" },
    { value: "name", label: "Nama A-Z" },
    { value: "category", label: "Kategori" },
    { value: "location", label: "Lokasi" },
];

const activeFilters = computed(() =>
    [
        form.q ? { key: "q", label: `Kata kunci: ${form.q}` } : null,
        form.category ? { key: "category", label: form.category } : null,
        form.location ? { key: "location", label: form.location } : null,
        form.date ? { key: "date", label: form.date } : null,
        form.sort !== "newest" ? { key: "sort", label: sortOptions.find((option) => option.value === form.sort)?.label ?? "Urutan" } : null,
    ].filter(Boolean),
);

function applyFilters() {
    router.get("/cari", form, {
        preserveState: true,
        replace: true,
    });
}

function clearFilters() {
    form.q = "";
    form.category = "";
    form.location = "";
    form.date = "";
    form.sort = "newest";
    form.per_page = 9;
    applyFilters();
}

function clearOneFilter(key) {
    form[key] = key === "sort" ? "newest" : "";
    applyFilters();
}

function itemHref(item) {
    return `/barang/${item.id}`;
}

function useFallbackImage(event) {
    event.target.src = fallbackImage;
}



function submitAndCloseFilters() {
    showMobileFilters.value = false;
    applyFilters();
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
    <Head>
        <title>Cari Barang Temuan - SIPB UYM</title>
        <meta head-key="description" name="description" content="Cari daftar barang temuan yang sudah dipublikasi oleh admin SIPB Universitas Yatsi Madani." />
        <meta head-key="og:title" property="og:title" content="Cari Barang Temuan - SIPB UYM" />
        <meta head-key="og:description" property="og:description" content="Cari daftar barang temuan yang sudah dipublikasi oleh admin SIPB Universitas Yatsi Madani." />
        <meta head-key="og:image" property="og:image" content="/assets/logo-uym.png" />
    </Head>
    <AppLayout title="Cari Barang" :show-page-header="false">
        <section id="barang-temuan" class="mx-auto mb-7 max-w-[1400px]">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-[#1a2134]">
                    Cari Barang
                </h1>
                <p class="mt-2 text-base font-medium text-[#64748b]">
                    Cari barang temuan yang sudah dipublikasi admin
                </p>
                </div>
                <button type="button" class="sipb-button-secondary inline-flex items-center justify-center gap-2 lg:hidden" @click="showMobileFilters = true">
                    <SlidersHorizontal class="h-4 w-4" />
                    Filter
                    <span v-if="activeFilters.length" class="grid h-5 min-w-5 place-items-center rounded-md bg-[#2737c9] px-1 text-[11px] font-extrabold text-white">
                        {{ activeFilters.length }}
                    </span>
                </button>
            </div>
        </section>

        <form
            id="filter-barang"
            class="mx-auto mb-5 max-w-[1400px]"
            @submit.prevent="applyFilters"
        >
            <label class="relative mb-5 block">
                <Search class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-[#64748b]" />
                <input
                    v-model="form.q"
                    class="h-10 w-full rounded-md border border-[#d8e0ea] bg-[#edf2f8] pl-11 pr-4 text-sm font-medium text-[#1a2134] outline-none placeholder:text-[#64748b] focus:border-[#2737c9] focus:bg-white"
                    placeholder="Cari berdasarkan nama, deskripsi, atau lokasi..."
                />
            </label>

            <div class="grid min-w-0 gap-6 lg:grid-cols-[260px_minmax(0,1fr)]">
                <aside class="hidden space-y-5 lg:sticky lg:top-[86px] lg:block lg:self-start">
                    <label class="block">
                        <span class="sipb-label">Kategori</span>
                        <select v-model="form.category" class="sipb-input">
                            <option value="">Semua Kategori</option>
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
                        <span class="sipb-label">Lokasi</span>
                        <select v-model="form.location" class="sipb-input">
                            <option value="">Semua Lokasi</option>
                            <option
                                v-for="location in locations"
                                :key="location"
                                :value="location"
                            >
                                {{ location }}
                            </option>
                        </select>
                    </label>

                    <div>
                        <p class="sipb-label mb-1">Tanggal laporan</p>
                        <p class="mb-3 text-xs leading-4 text-[#64748b]">
                            Kosongkan untuk semua tanggal.
                        </p>
                        <input
                            v-model="form.date"
                            type="date"
                            class="sipb-input"
                        />
                    </div>

                    <label class="block">
                        <span class="sipb-label">Urutkan</span>
                        <select v-model="form.sort" class="sipb-input">
                            <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <div class="border-t border-[#cbd5e1] pt-4">
                        <p class="text-sm font-medium text-[#64748b]">
                            Menampilkan {{ itemTotal }} barang
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="sipb-button-primary flex-1">
                            Terapkan
                        </button>
                        <button
                            type="button"
                            class="sipb-button-secondary flex-1"
                            @click="clearFilters"
                        >
                            Reset
                        </button>
                    </div>
                </aside>

                <div class="min-w-0">
                    <div
                        v-if="activeFilters.length"
                        class="mb-5 flex flex-wrap items-center gap-2"
                    >
                        <span class="text-xs font-bold uppercase text-[#747a8b]">
                            Filter aktif
                        </span>
                        <button
                            v-for="filter in activeFilters"
                            :key="filter.key"
                            type="button"
                            class="sipb-chip is-active"
                            @click="clearOneFilter(filter.key)"
                        >
                            {{ filter.label }}
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div
                        v-if="pageLoading"
                        class="relative grid min-w-0 gap-6 md:grid-cols-2 2xl:grid-cols-3"
                        style="animation: sipb-fade-in .2s ease both"
                    >
                        <article
                            v-for="index in skeletonRows"
                            :key="`public-search-skeleton-${index}`"
                            class="min-w-0 overflow-hidden rounded-md bg-white shadow-[-10px_12px_24px_rgba(203,213,225,0.18),10px_12px_24px_rgba(203,213,225,0.18),0_1px_4px_rgba(148,163,184,0.2)]"
                        >
                            <span class="sipb-skeleton h-[176px] w-full rounded-none"></span>
                            <div class="space-y-3 p-4">
                                <span class="sipb-skeleton h-5 w-4/5"></span>
                                <span class="sipb-skeleton h-6 w-20"></span>
                                <span class="sipb-skeleton h-3 w-32"></span>
                                <span class="sipb-skeleton h-4 w-full"></span>
                                <span class="sipb-skeleton h-3 w-2/3"></span>
                                <div class="flex items-center gap-2 pt-1">
                                    <span class="sipb-skeleton h-6 w-6"></span>
                                    <span class="sipb-skeleton h-3 w-28"></span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else-if="itemRows.length === 0" class="sipb-panel p-8 text-center">
                        <h2 class="sipb-section-title">Tidak ada barang sesuai filter</h2>
                        <p class="sipb-muted mt-1 text-sm">
                            Coba hapus sebagian filter, gunakan kata kunci yang lebih umum, atau cek lagi nanti setelah admin mempublish laporan baru.
                        </p>
                        <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
                        <button
                            v-if="activeFilters.length"
                            type="button"
                            class="sipb-button-secondary mt-5"
                            @click="clearFilters"
                        >
                            Reset filter
                        </button>
                            <Link href="/bantuan" class="sipb-button-primary inline-flex items-center justify-center">
                                Buka bantuan
                            </Link>
                        </div>
                    </div>

                    <TransitionGroup
                        v-else
                        name="sipb-list"
                        tag="div"
                        class="relative grid min-w-0 gap-6 md:grid-cols-2 2xl:grid-cols-3"
                        appear
                    >
                        <Link
                            v-for="(item, index) in itemRows"
                            :key="item.id"
                            :href="itemHref(item)"
                            :style="{ '--delay': `${index * 0.04}s` }"
                            class="min-w-0 overflow-hidden rounded-md bg-white shadow-[-10px_12px_24px_rgba(203,213,225,0.18),10px_12px_24px_rgba(203,213,225,0.18),0_1px_4px_rgba(148,163,184,0.2)]"
                        >
                            <div class="group h-[176px] bg-[#e2e8f0]">
                                <img
                                    :src="item.photo_url"
                                    :alt="item.name"
                                    class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                    @error="useFallbackImage"
                                />
                            </div>
                            <div class="space-y-2 p-4">
                                <h3 class="line-clamp-1 text-base font-extrabold text-[#1a2134]">
                                    {{ item.name }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="inline-flex rounded-md bg-[#e8f7f1] px-2 py-1 text-[11px] font-extrabold uppercase tracking-wide text-[#008a67]"
                                    >
                                        Temuan
                                    </span>
                                    <span :class="['inline-flex rounded-md border px-2 py-1 text-[11px] font-extrabold uppercase tracking-wide', statusClass(item.status)]">
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </div>
                                <p class="flex items-center gap-1 text-xs font-medium text-[#64748b]">
                                    <CalendarDays class="h-3.5 w-3.5" />
                                    {{ formatDate(item.found_at, { timeStyle: undefined }) }}
                                </p>
                                <p class="line-clamp-1 text-sm font-medium text-[#64748b]">
                                    {{ item.description }}
                                </p>
                                <p class="flex items-center gap-1 text-xs font-medium text-[#64748b]">
                                    <MapPin class="h-3.5 w-3.5" />
                                    {{ item.location }}
                                </p>
                                <p class="flex items-center gap-1 text-xs font-medium text-[#64748b]">
                                    <ClipboardCheck class="h-3.5 w-3.5" />
                                    Disimpan di pos lost & found.
                                    <span
                                        role="link"
                                        tabindex="0"
                                        class="font-bold text-[#2737c9] hover:underline"
                                        @click.prevent.stop="router.visit('/bantuan')"
                                        @keydown.enter.prevent.stop="router.visit('/bantuan')"
                                    >
                                        Jam layanan
                                    </span>
                                </p>
                                <div class="flex items-center gap-2 pt-1 text-xs font-medium text-[#64748b]">
                                    <span class="grid h-6 w-6 place-items-center rounded-md bg-[#2737c9] text-white">
                                        <UserRound class="h-3.5 w-3.5" />
                                    </span>
                                    {{ item.finder_name || "Admin SIPB" }}
                                </div>
                            </div>
                        </Link>
                    </TransitionGroup>

                    <Pagination
                        :meta="items"
                        @update:perPage="
                            (val) => {
                                form.per_page = val;
                                applyFilters();
                            }
                        "
                    />
                </div>
            </div>
        </form>

        <Transition name="sipb-fade">
            <div v-if="showMobileFilters" class="fixed inset-0 z-[65] bg-[#1a2134]/45 lg:hidden" @click.self="showMobileFilters = false">
                <section class="absolute bottom-0 left-0 right-0 max-h-[86vh] overflow-y-auto rounded-t-md bg-white p-5 shadow-[0_-24px_60px_rgba(26,33,52,0.22)]">
                    <div class="mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-extrabold text-[#1a2134]">Filter barang</h2>
                            <p class="mt-1 text-sm font-medium text-[#747a8b]">Sesuaikan pencarian barang temuan.</p>
                        </div>
                        <button type="button" class="sipb-icon-button" title="Tutup filter" @click="showMobileFilters = false">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="grid gap-4">
                        <label class="block">
                            <span class="sipb-label">Kategori</span>
                            <select v-model="form.category" class="sipb-input">
                                <option value="">Semua Kategori</option>
                                <option v-for="category in categories" :key="`mobile-category-${category}`" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="sipb-label">Lokasi</span>
                            <select v-model="form.location" class="sipb-input">
                                <option value="">Semua Lokasi</option>
                                <option v-for="location in locations" :key="`mobile-location-${location}`" :value="location">
                                    {{ location }}
                                </option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="sipb-label">Tanggal laporan</span>
                            <input v-model="form.date" type="date" class="sipb-input" />
                        </label>
                        <label class="block">
                            <span class="sipb-label">Urutkan</span>
                            <select v-model="form.sort" class="sipb-input">
                                <option v-for="option in sortOptions" :key="`mobile-sort-${option.value}`" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button type="button" class="sipb-button-secondary" @click="clearFilters">
                            Reset
                        </button>
                        <button type="button" class="sipb-button-primary" @click="submitAndCloseFilters">
                            Terapkan
                        </button>
                    </div>
                </section>
            </div>
        </Transition>

    </AppLayout>
</template>
