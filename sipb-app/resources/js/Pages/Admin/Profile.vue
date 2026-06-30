<script setup>
import { ref, watch } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AppLayout from "../../Shared/AppLayout.vue";
import { ChevronLeft, ChevronRight, Calendar } from "@lucide/vue";

const props = defineProps({
    user: Object,
    audits: Object,
    filters: Object,
    actions: Array,
});

const activeTab = ref("Data Diri");

const search = ref(props.filters.action || "");
const dateRange = ref(props.filters.date || "");

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            fn(...args);
        }, delay);
    };
};

watch(
    [search, dateRange],
    debounce(([newAction, newDate]) => {
        router.get(
            "/admin/profile",
            {
                action: newAction,
                date: newDate,
                per_page: props.filters.per_page,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300),
);

const resetFilter = () => {
    const today = new Date().toLocaleDateString("en-CA"); // gets YYYY-MM-DD
    search.value = "";
    dateRange.value = today;
};

const handlePageChange = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const handlePerPageChange = (e) => {
    router.get(
        "/admin/profile",
        {
            action: search.value,
            date: dateRange.value,
            per_page: e.target.value,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const profileFoto = "/assets/profile_foto.png";
const defaultFoto = "/assets/logo-uym.png";
</script>

<template>
    <Head title="Profil Saya" />
    <AppLayout title="Profil Saya" :admin="true">
        <!-- Tabs -->
        <div class="mb-6 flex gap-8 border-b border-gray-200 px-2">
            <button
                @click="activeTab = 'Data Diri'"
                :class="[
                    'pb-4 text-sm font-bold transition-colors relative',
                    activeTab === 'Data Diri'
                        ? 'text-[#2737c9]'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                Data Diri
                <span
                    v-if="activeTab === 'Data Diri'"
                    class="absolute bottom-0 left-0 h-0.5 w-full bg-[#2737c9]"
                ></span>
            </button>
            <button
                @click="activeTab = 'Riwayat Aktivitas'"
                :class="[
                    'pb-4 text-sm font-bold transition-colors relative',
                    activeTab === 'Riwayat Aktivitas'
                        ? 'text-[#2737c9]'
                        : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                Riwayat Aktivitas
                <span
                    v-if="activeTab === 'Riwayat Aktivitas'"
                    class="absolute bottom-0 left-0 h-0.5 w-full bg-[#2737c9]"
                ></span>
            </button>
        </div>

        <!-- Content: Data Diri -->
        <div v-if="activeTab === 'Data Diri'" class="rounded-xl sipb-panel p-8">
            <div class="flex flex-col md:flex-row gap-10">
                <!-- Profile Image -->
                <div class="shrink-0 flex justify-center">
                    <img
                        :src="profileFoto"
                        alt="Profile Photo"
                        class="h-40 w-40 rounded-full object-cover shadow-lg border-4 border-white ring-1 ring-gray-100"
                        :onerror="`this.src='${defaultFoto}'`"
                    />
                </div>
                <!-- Profile Data -->
                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1"
                        >
                            Nama
                        </p>
                        <p class="text-[15px] font-bold text-[#1a2134]">
                            {{ user.name }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1"
                        >
                            Role Admin
                        </p>
                        <p class="text-[15px] font-bold text-[#1a2134]">
                            {{ user.role_label }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1"
                        >
                            Akun Email
                        </p>
                        <p class="text-[15px] font-bold text-[#1a2134]">
                            {{ user.email }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1"
                        >
                            Status
                        </p>
                        <p class="text-[15px] font-bold text-[#1a2134]">
                            {{ user.status }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content: Riwayat Aktivitas -->
        <div
            v-if="activeTab === 'Riwayat Aktivitas'"
            class="grid grid-cols-1 lg:grid-cols-12 gap-6"
        >
            <!-- Left: Filter -->
            <div class="lg:col-span-4 rounded-xl sipb-panel p-6 h-max">
                <h3 class="font-extrabold text-[#1a2134] mb-5">Filter</h3>

                <div class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2"
                            >Jenis Aktivitas</label
                        >
                        <select
                            v-model="search"
                            class="sipb-input w-full bg-gray-50 border-gray-200"
                        >
                            <option value="">Semua Aktivitas</option>
                            <option
                                v-for="act in actions"
                                :key="act.value"
                                :value="act.value"
                            >
                                {{ act.label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-semibold text-gray-700 mb-2"
                            >Rentang Waktu</label
                        >
                        <input
                            type="date"
                            v-model="dateRange"
                            class="sipb-input w-full bg-gray-50 border-gray-200"
                            placeholder="Pilih Tanggal"
                        />
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button
                        @click="resetFilter"
                        class="flex-1 rounded-lg border border-gray-300 py-2.5 text-sm font-bold text-gray-600 hover:bg-gray-50"
                    >
                        Reset Filter
                    </button>
                    <!-- Search is reactive, no need for apply button unless we want manual submit -->
                </div>
            </div>

            <!-- Right: Timeline -->
            <div class="lg:col-span-8 space-y-4">
                <div
                    v-for="audit in audits.data"
                    :key="audit.id"
                    class="flex items-start gap-4 rounded-xl sipb-panel p-5"
                >
                    <div
                        class="mt-1 flex h-3 w-3 shrink-0 items-center justify-center rounded-full bg-[#2737c9] ring-4 ring-[#eef0f8]"
                    ></div>
                    <div class="min-w-0 flex-1">
                        <div
                            class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-semibold text-gray-500 mb-2"
                        >
                            <span class="flex items-center gap-1.5"
                                ><Calendar class="h-3.5 w-3.5" />
                                {{ audit.created_at }}</span
                            >
                            <span class="flex items-center gap-1.5"
                                ><MonitorPlay class="h-3.5 w-3.5" />
                                {{ audit.ip_address }}</span
                            >
                        </div>
                        <p class="text-sm font-bold text-[#1a2134]">
                            {{ audit.action_label }}
                            <span
                                v-if="audit.item_name !== '-'"
                                class="text-[#2737c9]"
                                >"{{ audit.item_name }}"</span
                            >
                        </p>
                        <p
                            v-if="audit.notes"
                            class="mt-1 text-sm text-gray-600 line-clamp-2"
                        >
                            {{ audit.notes }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="audits.data.length === 0"
                    class="rounded-xl sipb-panel p-10 text-center"
                >
                    <p class="font-semibold text-gray-500">
                        Belum ada riwayat aktivitas.
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="audits.data.length > 0"
                    class="mt-6 flex items-center justify-between rounded-xl sipb-panel p-4"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-600"
                            >Tampilkan</span
                        >
                        <select
                            @change="handlePerPageChange"
                            class="sipb-input py-1.5 text-sm w-32 border-gray-200"
                        >
                            <option value="5" :selected="filters.per_page == 5">
                                5 per halaman
                            </option>
                            <option
                                value="10"
                                :selected="filters.per_page == 10"
                            >
                                10 per halaman
                            </option>
                            <option
                                value="20"
                                :selected="filters.per_page == 20"
                            >
                                20 per halaman
                            </option>
                        </select>
                        <span class="text-sm font-semibold text-gray-500"
                            >Total {{ audits.total }} data</span
                        >
                    </div>

                    <div class="flex gap-1">
                        <button
                            v-for="(link, idx) in audits.links"
                            :key="idx"
                            @click="handlePageChange(link.url)"
                            :disabled="!link.url || link.active"
                            :class="[
                                'min-w-[32px] rounded-md px-2.5 py-1.5 text-sm font-bold transition-colors disabled:opacity-50 disabled:cursor-not-allowed',
                                link.active
                                    ? 'bg-[#2737c9] text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                            ]"
                        >
                            <ChevronLeft v-if="idx === 0" class="h-4 w-4" />
                            <ChevronRight v-else-if="idx === audits.links.length - 1" class="h-4 w-4" />
                            <span v-else>{{ link.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
