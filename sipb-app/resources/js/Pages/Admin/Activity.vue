<script setup>
import { Link, router } from "@inertiajs/vue3";
import { ArrowRight, CalendarDays, Download, Filter, Globe2, ListChecks, Search, X } from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import ActiveFilters from "../../Shared/ActiveFilters.vue";
import AppLayout from "../../Shared/AppLayout.vue";
import FilterDrawer from "../../Shared/FilterDrawer.vue";
import Pagination from "../../Shared/Pagination.vue";
import SearchToolbar from "../../Shared/SearchToolbar.vue";
import { formatDate, statusLabel } from "../../Shared/status";

const props = defineProps({
    audits: Object,
    filters: Object,
    actions: Array,
    dailyStats: Array,
    storageStats: Object,
});

const pageLoading = ref(false);
const skeletonRows = computed(() => Math.min(Number(filters.per_page) || 10, 10));
const isDenseTable = computed(() => Number(filters.per_page) >= 20);
const isVeryDenseTable = computed(() => Number(filters.per_page) >= 50);
let removePageStartListener = null;
let removePageFinishListener = null;

const activeTab = ref('riwayat');

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

function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

const auditRows = computed(() => props.audits.data ?? props.audits);
const auditTotal = computed(() => props.audits.total ?? auditRows.value.length);
const filters = reactive({
    q: props.filters.q ?? "",
    date: props.filters.date ?? "",
    action: props.filters.action ?? "",
    per_page: props.filters.per_page ?? 10,
});

const showAdvancedFilters = ref(false);
const activeFilters = computed(() => [
    filters.q ? { key: "q", label: `Keyword: ${filters.q}` } : null,
    filters.date ? { key: "date", label: `Tanggal: ${filters.date}` } : null,
    filters.action ? { key: "action", label: `Aksi: ${filters.action}` } : null,
].filter(Boolean));

function applyFilters() {
    router.get("/admin/aktivitas", filters, {
        preserveState: true,
        replace: true,
    });
}

function clearFilter(key) {
    filters[key] = "";
    applyFilters();
}

function resetFilters() {
    filters.q = "";
    filters.date = "";
    filters.action = "";
    applyFilters();
}
</script>

<template>
    <AppLayout title="Log Aktivitas" admin>

        <!-- Tabs -->
        <div class="-mx-4 md:-mx-8 mb-6 flex border-b border-[#e2e8f0] px-4 md:px-8">
            <button 
                :class="['mr-8 border-b-2 px-1 py-3.5 text-sm', activeTab === 'riwayat' ? 'border-[#2737c9] font-bold text-[#2737c9]' : 'border-transparent font-semibold text-[#747a8b] hover:text-[#1a2134]']"
                @click="activeTab = 'riwayat'"
            >
                Riwayat Aktivitas
            </button>
            <button 
                :class="['border-b-2 px-1 py-3.5 text-sm', activeTab === 'storage' ? 'border-[#2737c9] font-bold text-[#2737c9]' : 'border-transparent font-semibold text-[#747a8b] hover:text-[#1a2134]']"
                @click="activeTab = 'storage'"
            >
                Penggunaan Storage
            </button>
        </div>

        <div v-if="activeTab === 'riwayat'">

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
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md border border-[#00bf8e] px-4 py-1.5 text-sm font-bold text-[#00bf8e] transition-colors hover:bg-[#00bf8e] hover:text-white"
                >
                    <Download class="h-4 w-4" /> Export Excel
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
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]">Tanggal</span>
                <input v-model="filters.date" type="date" class="w-full rounded-md border border-[#e2e8f0] px-3 py-2.5 text-sm focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]" />
            </label>
            <label class="block">
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]">Jenis aksi</span>
                <select v-model="filters.action" class="w-full rounded-md border border-[#e2e8f0] px-3 py-2.5 text-sm focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]">
                    <option value="">Semua aksi</option>
                    <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
                </select>
            </label>
        </FilterDrawer>

        <section>
            <div class="grid gap-3 p-4 md:hidden">
                <template v-if="pageLoading">
                    <div v-for="index in skeletonRows" :key="`card-sk-${index}`" class="rounded-md sipb-panel p-4">
                        <div class="space-y-2">
                            <span class="sipb-skeleton h-4 w-3/4"></span>
                            <span class="sipb-skeleton h-3 w-1/2"></span>
                            <span class="sipb-skeleton h-6 w-20"></span>
                            <span class="sipb-skeleton h-3 w-full"></span>
                        </div>
                    </div>
                </template>
                <div v-else-if="auditRows.length === 0" class="rounded-md bg-[#f6f7fa] p-5 text-center text-sm font-medium text-[#747a8b]">
                    Belum ada aktivitas tercatat.
                </div>
                <template v-else>
                    <div v-for="(audit, index) in auditRows" :key="`card-${audit.id}`" class="rounded-md sipb-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-xs font-bold text-[#747a8b]">{{ formatDate(audit.created_at) }}</span>
                            <span class="inline-flex shrink-0 rounded-md bg-[#edf2ff] px-2 py-0.5 text-[11px] font-bold text-[#2737c9]">
                                {{ audit.action }}
                            </span>
                        </div>
                        <div class="mt-3 space-y-1.5 text-sm">
                            <p><span class="font-bold text-[#1a2134]">Aktor:</span> <span class="text-[#64748b]">{{ audit.user?.name || 'Sistem / Guest' }}</span></p>
                            <p><span class="font-bold text-[#1a2134]">Barang:</span> <span class="text-[#64748b]">{{ audit.item?.name || '-' }}</span></p>
                            <p v-if="audit.from_status || audit.to_status">
                                <span class="font-bold text-[#1a2134]">Status:</span>
                                <span class="text-[#64748b]">{{ statusLabel(audit.from_status) }} <ArrowRight class="inline h-3 w-3" /> {{ statusLabel(audit.to_status) }}</span>
                            </p>
                            <p v-if="audit.notes"><span class="font-bold text-[#1a2134]">Catatan:</span> <span class="text-[#64748b] italic">{{ audit.notes }}</span></p>
                            <p><span class="font-bold text-[#1a2134]">IP:</span> <span class="text-[#64748b]">{{ audit.ip_address || '-' }}</span></p>
                        </div>
                    </div>
                </template>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-[1020px] w-full text-sm">
                    <thead class="border-b border-[#e2e8f0] text-left">
                        <tr>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">No</th>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">Waktu</th>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">Aktor</th>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">Aksi</th>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">Target (Barang)</th>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">Detail & Catatan</th>
                            <th :class="[isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3', 'font-bold text-[#1a2134]']">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="pageLoading">
                            <tr v-for="index in skeletonRows" :key="`activity-skeleton-${index}`" class="align-middle">
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-8"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-32"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-28"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-6 w-20"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-36"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-24"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-20"></span></td>
                            </tr>
                        </template>
                        <tr v-else-if="auditRows.length === 0" class="border-b border-[#f1f5f9] bg-white">
                            <td colspan="7" class="px-4 py-8 text-center text-[#747a8b]">
                                Belum ada aktivitas tercatat.
                            </td>
                        </tr>
                        <tr v-else v-for="(audit, index) in auditRows" :key="audit.id" class="border-b border-[#f1f5f9] align-middle odd:bg-white even:bg-[#f8f9fd] hover:bg-[#f1f5f9]">
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3', 'text-[#1a2134]']">{{ (props.audits.current_page - 1) * props.audits.per_page + index + 1 }}</td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3', 'text-[#747a8b]']">{{ formatDate(audit.created_at) }}</td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <span class="font-bold text-[#1a2134]">{{ audit.user?.name || 'Sistem / Guest' }}</span>
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <span :class="['inline-flex rounded-md bg-[#edf2ff] text-xs font-bold text-[#2737c9]', isDenseTable ? 'px-2 py-0.5' : 'px-2.5 py-1']">
                                    {{ audit.action }}
                                </span>
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <span class="font-bold text-[#1a2134]">{{ audit.item?.name || '-' }}</span>
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <div class="text-[#64748b]">
                                    <span v-if="audit.from_status || audit.to_status">
                                        {{ statusLabel(audit.from_status) }}
                                        <ArrowRight class="mx-1 inline h-3.5 w-3.5" />
                                        {{ statusLabel(audit.to_status) }}
                                    </span>
                                    <span v-else>-</span>
                                    <p v-if="audit.notes" class="mt-1 text-xs text-[#747a8b] italic">{{ audit.notes }}</p>
                                </div>
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3', 'text-[#747a8b]']">
                                {{ audit.ip_address || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                <Pagination :meta="audits" @update:perPage="val => { filters.per_page = val; applyFilters() }" />
            </div>
        </section>
        </div>

        <div v-else-if="activeTab === 'storage'">
            <h3 class="mb-6 text-[17px] font-bold text-[#1a2134]">Penggunaan Storage Database</h3>
            
            <div class="grid gap-6 md:grid-cols-3 mb-8">
                <div class="rounded-xl border border-[#e2e8f0] bg-white p-6 shadow-sm">
                    <div class="mb-2 text-sm font-semibold text-[#747a8b]">Total Item Tersimpan</div>
                    <div class="text-3xl font-black text-[#1a2134]">{{ storageStats.total_items }}</div>
                </div>
                
                <div class="rounded-xl border border-[#e2e8f0] bg-white p-6 shadow-sm">
                    <div class="mb-2 text-sm font-semibold text-[#747a8b]">Item Dengan Foto</div>
                    <div class="text-3xl font-black text-[#1a2134]">{{ storageStats.items_with_photo }}</div>
                </div>

                <div class="rounded-xl border border-[#e2e8f0] bg-white p-6 shadow-sm">
                    <div class="mb-2 text-sm font-semibold text-[#747a8b]">Total Ukuran Foto</div>
                    <div class="text-3xl font-black text-[#2737c9]">{{ formatBytes(storageStats.total_photo_size_bytes) }}</div>
                </div>
            </div>
            
            <div class="rounded-xl bg-[#f8f9fd] p-6 border border-[#e2e8f0]">
                <h4 class="font-bold text-[#1a2134] mb-2 flex items-center gap-2">
                    <Globe2 class="h-5 w-5 text-[#2737c9]" />
                    Informasi Penyimpanan
                </h4>
                <p class="text-sm text-[#747a8b] leading-relaxed">
                    Sistem saat ini menyimpan foto secara langsung di dalam database (Base64) untuk kemudahan portabilitas. 
                    Ukuran foto rata-rata adalah <strong>{{ formatBytes(storageStats.items_with_photo > 0 ? storageStats.total_photo_size_bytes / storageStats.items_with_photo : 0) }}</strong> per item.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
