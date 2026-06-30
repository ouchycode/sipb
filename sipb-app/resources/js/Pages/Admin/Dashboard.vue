<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import {
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    HelpCircle,
    PackageOpen,
} from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import AppLayout from "../../Shared/AppLayout.vue";
import { formatDate, statusClass, statusLabel } from "../../Shared/status";

const props = defineProps({
    stats: Object,
    latest: Array,
    insights: Object,
});

const page = usePage();
const currentTime = ref(new Date());
const calendarCursor = ref(new Date());
const campusLogo = "/assets/logo-uym.png";
const civitasLogo = "/assets/civitas_lfs.png";
const mobileMenuIcon = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDIiIGhlaWdodD0iNDIiIHZpZXdCb3g9IjAgMCA0MiA0MiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjEiIGN5PSIyMSIgcj0iMjEiIGZpbGw9IiMxNjE4MUUiLz4KPHBhdGggZD0iTTEzIDE1QzEzIDE0LjQ0NzcgMTMuNDQ3NyAxNCAxNCAxNEgyNkMyNi41NTIzIDE0IDI3IDE0LjQ0NzcgMjcgMTVDMjcgMTUuNTUyMyAyNi41NTIzIDE2IDI2IDE2SDE0QzEzLjQ0NzcgMTYgMTMgMTUuNTUyMyAxMyAxNVoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0xMyAyMUMxMyAyMC40NDc3IDEzLjQ0NzcgMjAgMTQgMjBIMjlDMjkuNTUyMyAyMCAzMCAyMC40NDc3IDMwIDIxQzMwIDIxLjU1MjMgMjkuNTUyMyAyMiAyOSAyMkgxNEMxMy40NDc3IDIyIDEzIDIxLjU1MjMgMTMgMjFaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjIgMjdDMjIgMjYuNDQ3NyAyMS41NTIzIDI2IDIxIDI2SDE0QzEzLjQ0NzcgMjYgMTMgMjYuNDQ3NyAxMyAyN0MxMyAyNy41NTIzIDEzLjQ0NzcgMjggMTQgMjhIMjFDMjEuNTUyMyAyOCAyMiAyNy41NTIzIDIyIDI3WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg==";
let clockTimer = null;

const insightsData = ref(props.insights);
const selectedPeriod = ref("");

async function fetchInsights() {
    const period = selectedPeriod.value;
    const url = period ? `/admin/insights?period=${period}` : '/admin/insights';
    try {
        const res = await fetch(url);
        if (res.ok) {
            insightsData.value = await res.json();
        }
    } catch {
        // fallback to existing data
    }
}

const todayLabel = computed(() => new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    timeZoneName: "short",
}).format(currentTime.value));

const monthLabel = computed(() => new Intl.DateTimeFormat("id-ID", {
    month: "long",
    year: "numeric",
}).format(calendarCursor.value));

const reportDays = computed(() => new Set((props.latest ?? [])
    .map((item) => item.found_at ? new Date(item.found_at) : null)
    .filter((date) => date && date.getMonth() === calendarCursor.value.getMonth() && date.getFullYear() === calendarCursor.value.getFullYear())
    .map((date) => date.getDate())));

const calendarDays = computed(() => {
    const cursor = calendarCursor.value;
    const now = currentTime.value;
    const year = cursor.getFullYear();
    const month = cursor.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const blanks = Array.from({ length: firstDay }, (_, index) => ({
        key: `blank-${index}`,
        day: "",
        isToday: false,
    }));
    const days = Array.from({ length: daysInMonth }, (_, index) => {
        const day = index + 1;

        return {
            key: `day-${day}`,
            day,
            isToday: day === now.getDate() && month === now.getMonth() && year === now.getFullYear(),
            hasEvent: reportDays.value.has(day),
        };
    });

    return [...blanks, ...days];
});

const statCards = computed(() => [
    {
        label: "Total Barang",
        value: props.stats.total,
        icon: PackageOpen,
        tone: "bg-[#feae37] text-white",
    },
    {
        label: "Tersedia",
        value: props.stats.available,
        icon: ClipboardList,
        tone: "bg-[#00bf8e] text-white",
    },
    {
        label: "Selesai",
        value: props.stats.claimed,
        icon: CheckCircle2,
        tone: "bg-[#2737c9] text-white",
    },
    {
        label: "Kadaluarsa",
        value: props.stats.expired,
        icon: PackageOpen,
        tone: "bg-[#7957d5] text-white",
    },
]);

const recentItems = computed(() => (props.latest ?? []).slice(0, 4));

const chartMaxY = computed(() => {
    const maxData = Math.max(...(insightsData.value?.categories?.map((c) => c.total) || [0]), 10);
    return Math.ceil(maxData * 1.2);
});

function chartRows(rows, maxScale = null) {
    const values = rows ?? [];
    const max = maxScale !== null ? maxScale : Math.max(...values.map((row) => row.total), 1);

    return values.map((row) => ({
        ...row,
        width: `${Math.max((row.total / max) * 100, 7)}%`,
        height: `${Math.max((row.total / max) * 100, 12)}%`,
    }));
}

const categoryChart = computed(() => chartRows(insightsData.value?.categories, chartMaxY.value));
const locationChart = computed(() => chartRows(insightsData.value?.locations));

const trendChart = computed(() => {
    let months = [...(insightsData.value?.months ?? [])].reverse();
    if (months.length === 0) return [];

    const maxTotal = Math.max(...months.map((m) => m.total), 4);

    const w = 1000;
    const h = 180;
    const step = months.length > 1 ? w / (months.length - 1) : w;

    return months.map((item, index) => {
        const x = months.length === 1 ? w / 2 : index * step;
        const y = h - (item.total / maxTotal) * h + 20;

        const date = new Date(item.month + '-01');
        const label = new Intl.DateTimeFormat("id-ID", { month: "short", year: "2-digit" }).format(date);

        return { ...item, label, x, y };
    });
});

const trendPath = computed(() => {
    if (!trendChart.value.length) return '';
    if (trendChart.value.length === 1) {
        const p = trendChart.value[0];
        return `M 0 ${p.y} L 1000 ${p.y}`;
    }
    return trendChart.value.map((point, index) => 
        (index === 0 ? 'M' : 'L') + ` ${point.x} ${point.y}`
    ).join(' ');
});

const trendArea = computed(() => {
    if (!trendChart.value.length) return '';
    if (trendChart.value.length === 1) {
        const p = trendChart.value[0];
        return `M 0 ${p.y} L 1000 ${p.y} L 1000 220 L 0 220 Z`;
    }
    const last = trendChart.value[trendChart.value.length - 1];
    const first = trendChart.value[0];
    
    return trendPath.value + ` L ${last.x} 220 L ${first.x} 220 Z`;
});

function openAdminMenu() {
    window.dispatchEvent(new CustomEvent("sipb:open-admin-sidebar"));
}

function moveCalendarMonth(step) {
    const next = new Date(calendarCursor.value);
    next.setMonth(next.getMonth() + step, 1);
    calendarCursor.value = next;
}

onMounted(() => {
    clockTimer = window.setInterval(() => {
        currentTime.value = new Date();
    }, 1000);

});

onBeforeUnmount(() => {
    if (clockTimer) {
        window.clearInterval(clockTimer);
    }
});
</script>

<template>
    <AppLayout title="Beranda admin" admin>
        <header class="mb-6 hidden items-center justify-between gap-6 lg:flex">
            <div>
                <h1 class="text-2xl font-extrabold leading-tight text-[#071735]">
                    Hi, {{ page.props.auth.user?.name || "Admin" }} <span aria-hidden="true">&#128075;</span>
                </h1>
                <p class="mt-1 text-sm font-semibold text-[#747a8b]">
                        Selamat Datang Di SIPB
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-5">
                <p class="whitespace-nowrap text-sm font-semibold text-[#747a8b]">
                    {{ todayLabel }}
                </p>
                <div class="flex items-center gap-2">
                    <img :src="civitasLogo" alt="Logo Civitas" class="h-12 w-12 object-contain" />
                </div>
            </div>
        </header>

        <section class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="min-w-0">
                <header class="mb-6 flex items-start justify-between gap-4 lg:hidden">
                    <div class="min-w-0">
                        <h1 class="text-xl font-extrabold leading-7 text-[#1a2134]">
                            Hi, {{ page.props.auth.user?.name || "Admin" }} <span aria-hidden="true">&#128075;</span>
                        </h1>
                        <p class="mt-1 text-xs font-bold leading-4 text-[#747a8b]">
                                Selamat Datang Di SIPB
                        </p>
                    </div>
                    <button
                        type="button"
                        class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-full"
                        title="Buka menu"
                        @click="openAdminMenu"
                    >
                        <img :src="mobileMenuIcon" alt="Buka menu" class="h-[42px] w-[42px]" />
                    </button>
                </header>

                <section class="mb-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
                    <Link
                        v-for="card in statCards"
                        :key="card.label"
                        href="/admin/barang"
                        class="sipb-panel flex min-h-[110px] flex-col justify-between p-4 transition-all hover:shadow-[0_8px_24px_rgba(39,55,201,0.08)]"
                    >
                        <p class="text-sm font-semibold text-[#747a8b]">{{ card.label }}</p>
                        <div class="flex items-end justify-between gap-3">
                            <p class="text-[28px] font-extrabold leading-none text-[#071735]">{{ card.value }}</p>
                            <span :class="['grid h-10 w-10 shrink-0 place-items-center rounded-lg', card.tone]">
                                <component :is="card.icon" class="h-5 w-5" />
                            </span>
                        </div>
                    </Link>
                </section>
                <section class="sipb-panel mb-4 flex items-start gap-4 border-l-4 border-[#2737c9] px-4 py-4">
                    <div class="grid h-8 w-8 shrink-0 place-items-center rounded-md bg-[#edf2ff] text-[#2737c9]">
                        <HelpCircle class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-[15px] font-extrabold text-[#1a2134]">Panduan Cepat Admin</h3>
                        <p class="mt-1 text-sm font-medium leading-relaxed text-[#64748b]">
                            Pastikan Anda segera mengubah status barang menjadi <strong class="text-[#1a2134]">"Selesai"</strong> setelah barang diambil oleh pemiliknya agar data di sistem selalu akurat.
                        </p>
                    </div>
                </section>



                <section class="sipb-panel mb-4 p-4 md:p-5">
                    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex flex-wrap items-center gap-6">
                            <h2 class="text-[17px] font-extrabold text-[#071735]">Grafik Kategori Barang</h2>
                            <span class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a2134]">
                                <span class="h-3.5 w-3.5 rounded-sm bg-[#5ac8ee]"></span>
                                Total Laporan
                            </span>
                        </div>
                        <select
                            v-model="selectedPeriod"
                            class="sipb-input max-w-[190px] !min-h-10 !py-2 !text-sm text-[#747a8b]"
                            @change="fetchInsights"
                        >
                            <option value="">Semua waktu</option>
                            <option value="7">7 hari</option>
                            <option value="30">30 hari</option>
                            <option value="90">90 hari</option>
                        </select>
                    </div>

                    <div class="relative h-[250px] w-full pt-4">
                        <!-- Y-Axis Labels -->
                        <div class="absolute bottom-6 left-0 top-4 flex w-10 flex-col justify-between text-right text-[11px] font-semibold text-[#9da3b1]">
                            <span>{{ Math.ceil(chartMaxY) }}</span>
                            <span>{{ Math.ceil(chartMaxY * 0.75) }}</span>
                            <span>{{ Math.ceil(chartMaxY * 0.5) }}</span>
                            <span>{{ Math.ceil(chartMaxY * 0.25) }}</span>
                            <span>0</span>
                        </div>
                        
                        <!-- Chart Area -->
                        <div class="absolute bottom-6 left-12 right-4 top-4 border-b border-l border-[#e6e9ed]">
                            <!-- Horizontal Grid Lines -->
                            <div class="absolute inset-0 flex flex-col justify-between pb-[1px]">
                                <div class="w-full border-b border-dashed border-[#e6e9ed]"></div>
                                <div class="w-full border-b border-dashed border-[#e6e9ed]"></div>
                                <div class="w-full border-b border-dashed border-[#e6e9ed]"></div>
                                <div class="w-full border-b border-dashed border-[#e6e9ed]"></div>
                                <div class="w-full border-b border-dashed border-[#e6e9ed] opacity-0"></div> <!-- Bottom zero line covered by solid border -->
                            </div>

                            <!-- Bars -->
                            <div v-if="categoryChart.length" class="absolute inset-0 flex items-end justify-around px-4">
                                <div
                                    v-for="row in categoryChart"
                                    :key="row.category"
                                    class="group relative flex h-full flex-col justify-end items-center"
                                >
                                    <!-- Tooltip -->
                                    <div class="absolute bottom-full mb-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <span class="rounded bg-[#1a2134] px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm">
                                            {{ row.total }}
                                        </span>
                                    </div>
                                    <!-- Bar -->
                                    <div class="w-3 rounded-t-md bg-[#5ac8ee] transition-all duration-300" :style="{ height: row.height }"></div>
                                    
                                    <!-- X-Axis Label -->
                                    <div class="absolute top-full mt-2 w-24 text-center">
                                        <span class="block truncate text-[11px] font-semibold text-[#747a8b]">
                                            {{ row.category }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="absolute inset-0 grid place-items-center text-sm font-semibold text-[#747a8b]">
                                Belum ada data grafik.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="sipb-panel mb-4 p-4 md:p-5">
                    <div class="mb-4 flex flex-wrap items-center gap-4">
                        <h2 class="text-[17px] font-extrabold text-[#071735]">Tren Laporan per Bulan</h2>
                        <span class="inline-flex items-center gap-2 text-sm font-semibold text-[#1a2134]">
                            <span class="h-3.5 w-3.5 rounded-sm bg-[#00bf8e]"></span>
                            Jumlah Laporan
                        </span>
                    </div>
                    <div v-if="trendChart.length" class="relative w-full" style="aspect-ratio: 1000 / 240">
                        <svg viewBox="0 0 1000 240" class="h-full w-full overflow-visible">
                            <path :d="trendArea" fill="rgba(0, 191, 142, 0.08)" />
                            <path :d="trendPath" fill="none" stroke="#00bf8e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <circle
                                v-for="point in trendChart"
                                :key="point.month"
                                :cx="point.x" :cy="point.y" r="4"
                                fill="#fff" stroke="#00bf8e" stroke-width="2.5"
                                class="cursor-pointer"
                            >
                                <title>{{ point.label }}: {{ point.total }} laporan</title>
                            </circle>
                            <text
                                v-for="point in trendChart"
                                :key="`label-${point.month}`"
                                :x="point.x" y="230"
                                text-anchor="middle"
                                class="text-xs font-semibold"
                                fill="#747a8b"
                            >{{ point.label }}</text>
                        </svg>
                    </div>
                    <p v-else class="py-8 text-center text-sm font-semibold text-[#747a8b]">
                        Belum ada data tren.
                    </p>
                </section>

                <section class="sipb-panel mb-4 overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-[#e6e9ed] px-4 py-4 sm:flex-row sm:items-center sm:justify-between md:px-5">
                        <div>
                            <h2 class="text-base font-extrabold text-[#071735]">Barang terbaru</h2>
                            <p class="mt-1 text-sm font-semibold text-[#747a8b]">4 laporan terakhir yang masuk ke sistem.</p>
                        </div>
                        <Link href="/admin/barang" class="text-sm font-bold text-[#2737c9]">
                            Kelola data
                        </Link>
                    </div>
                    <div class="grid divide-y divide-[#eef0f5] lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                        <Link
                            v-for="item in recentItems"
                            :key="item.id"
                            href="/admin/barang"
                            class="min-w-0 px-4 py-4 transition-colors hover:bg-[#fafbff] md:px-5"
                        >
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-[#1a2134]">{{ item.name }}</p>
                                    <p class="mt-1 truncate text-xs font-semibold text-[#747a8b]">{{ item.location }}</p>
                                </div>
                                <span :class="['shrink-0 rounded-md border px-2 py-1 text-[11px] font-medium', statusClass(item.status)]">
                                    {{ statusLabel(item.status) }}
                                </span>
                            </div>
                            <p class="mt-2 flex items-center gap-1 text-xs font-semibold text-[#747a8b]">
                                <CalendarDays class="h-3.5 w-3.5" />
                                {{ formatDate(item.found_at) }}
                            </p>
                        </Link>
                        <div v-if="recentItems.length === 0" class="px-4 py-8 text-center text-sm font-semibold text-[#747a8b] md:px-5">
                            Belum ada data barang.
                        </div>
                    </div>
                </section>


            </div>

            <aside class="grid gap-5 lg:grid-cols-2 2xl:block 2xl:space-y-5">
                <section class="sipb-panel p-4 md:p-5">
                    <h2 class="mb-4 text-lg font-extrabold text-[#1a2134]">Kalender</h2>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[#2737c9] text-[#2737c9] transition-colors hover:bg-[#2737c9] hover:text-white"
                            title="Bulan sebelumnya"
                            @click="moveCalendarMonth(-1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                        <p class="min-w-0 flex-1 text-center text-base font-extrabold text-[#1a2134]">{{ monthLabel }}</p>
                        <button
                            type="button"
                            class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[#2737c9] text-[#2737c9] transition-colors hover:bg-[#2737c9] hover:text-white"
                            title="Bulan berikutnya"
                            @click="moveCalendarMonth(1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="grid grid-cols-7 gap-y-1 text-center">
                        <span v-for="day in ['S', 'S', 'R', 'K', 'J', 'S', 'M']" :key="day" class="py-1 text-xs font-extrabold text-[#1a2134]">
                            {{ day }}
                        </span>
                        <span
                            v-for="day in calendarDays"
                            :key="day.key"
                            :class="[
                                'relative mx-auto grid h-8 w-8 place-items-center rounded-full text-xs font-semibold',
                                day.isToday ? 'bg-[#2737c9] font-extrabold text-white' : day.hasEvent ? 'font-bold text-[#2737c9]' : 'text-[#64748b]',
                            ]"
                        >
                            {{ day.day }}
                            <span v-if="day.hasEvent && !day.isToday" class="absolute bottom-0 h-1 w-1 rounded-full bg-[#00bf8e]"></span>
                        </span>
                    </div>
                    <div class="mt-3 flex justify-end">
                        <div class="flex items-center gap-2 text-[10px] font-bold text-[#747a8b]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#00bf8e]"></span>
                            Ada laporan
                        </div>
                    </div>
                </section>

                <section class="sipb-panel p-5 md:p-6">
                    <h2 class="text-lg font-extrabold text-[#1a2134]">Lokasi tersering</h2>
                    <div class="mt-5 space-y-4">
                        <div v-for="row in locationChart" :key="row.location" class="text-sm">
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <span class="truncate font-semibold text-[#64748b]">{{ row.location }}</span>
                                <span class="text-xs font-extrabold text-[#1a2134]">{{ row.total }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-md bg-[#edf2f8]">
                                <div class="h-full rounded-md bg-[#00bf8e]" :style="{ width: row.width }"></div>
                            </div>
                        </div>
                        <p v-if="locationChart.length === 0" class="text-sm font-semibold text-[#747a8b]">
                            Belum ada data lokasi.
                        </p>
                    </div>
                </section>
            </aside>
        </section>
    </AppLayout>
</template>
