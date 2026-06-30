<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import {
    AlertTriangle,
    CheckCircle2,
    ChevronRight,
    ChevronDown,
    ClipboardList,
    HelpCircle,
    History,
    Home,
    Image,
    LayoutDashboard,
    ListChecks,
    LogOut,
    LogIn,
    Search,
    User,
    UsersRound,
    X,
} from "@lucide/vue";
import { Toaster, toast } from "vue-sonner";
import "vue-sonner/style.css";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import AiChatWidget from "./AiChatWidget.vue";

const props = defineProps({
    title: {
        type: String,
        default: "SIPB UYM",
    },
    admin: {
        type: Boolean,
        default: false,
    },
    showPageHeader: {
        type: Boolean,
        default: true,
    },
    showPublicSidebar: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const isLoading = ref(false);
const publicSidebarOpen = ref(false);
const adminSidebarOpen = ref(false);
const profileDropdownOpen = ref(false);
const reportDropdownOpen = ref(false);
const dismissedFlashKey = ref("");
const isPublicHome = computed(() => page.url === "/");
const isAdminDashboard = computed(() => props.admin && page.url === "/admin");
const hasPublicSidebar = computed(
    () => !props.admin && !isPublicHome.value && props.showPublicSidebar,
);
const showAiAssistant = computed(
    () =>
        !props.admin &&
        !page.url.startsWith("/login") &&
        !page.url.startsWith("/lapor-temuan"),
);

const publicNavItems = computed(() => {
    const items = [
        {
            href: "/",
            label: "Beranda",
            icon: Home,
            title: "Beranda",
            show: true,
        },
        {
            href: "/cari",
            label: "Cari Barang",
            icon: Search,
            title: "Cari Barang",
            show: true,
        },
        {
            href: "/bantuan",
            label: "Bantuan",
            icon: HelpCircle,
            title: "Bantuan",
            show: true,
        },
    ];

    return items.filter((item) => item.show);
});
const adminNavItems = computed(() =>
    [
        {
            href: "/admin",
            label: "Dashboard",
            icon: LayoutDashboard,
            show: Boolean(page.props.auth?.user),
        },
        {
            href: "/admin/barang",
            label: "Kelola Barang",
            icon: ClipboardList,
            show: Boolean(page.props.auth?.user),
        },
        {
            href: "/admin/foto",
            label: "Upload Foto",
            icon: Image,
            show: Boolean(page.props.auth?.user),
        },
        {
            href: "/admin/history",
            label: "History",
            icon: History,
            show: Boolean(page.props.auth?.user),
        },
        {
            href: "/admin/aktivitas",
            label: "Log Aktivitas",
            icon: ListChecks,
            show: Boolean(page.props.auth?.user?.is_super_admin),
        },
        {
            href: "/admin/users",
            label: "Kelola User",
            icon: UsersRound,
            show: Boolean(page.props.auth?.user?.is_super_admin),
        },
    ].filter((item) => item.show),
);
const campusLogo = "/assets/logo-uym.png";
const civitasLogo = "/assets/civitas_lfs.png";
const defaultProfileFoto = "/assets/profile_foto.png";
const mobileMenuIcon =
    "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDIiIGhlaWdodD0iNDIiIHZpZXdCb3g9IjAgMCA0MiA0MiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjEiIGN5PSIyMSIgcj0iMjEiIGZpbGw9IiMxNjE4MUUiLz4KPHBhdGggZD0iTTEzIDE1QzEzIDE0LjQ0NzcgMTMuNDQ3NyAxNCAxNCAxNEgyNkMyNi41NTIzIDE0IDI3IDE0LjQ0NzcgMjcgMTVDMjcgMTUuNTUyMyAyNi41NTIzIDE2IDI2IDE2SDE0QzEzLjQ0NzcgMTYgMTMgMTUuNTUyMyAxMyAxNVoiIGZpbGw9IndoaXRlIi8+CjxwYXRoIGQ9Ik0xMyAyMUMxMyAyMC40NDc3IDEzLjQ0NzcgMjAgMTQgMjBIMjlDMjkuNTUyMyAyMCAzMCAyMC40NDc3IDMwIDIxQzMwIDIxLjU1MjMgMjkuNTUyMyAyMiAyOSAyMkgxNEMxMy40NDc3IDIyIDEzIDIxLjU1MjMgMTMgMjFaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMjIgMjdDMjIgMjYuNDQ3NyAyMS41NTIzIDI2IDIxIDI2SDE0QzEzLjQ0NzcgMjYgMTMgMjYuNDQ3NyAxMyAyN0MxMyAyNy41NTIzIDEzLjQ0NzcgMjggMTQgMjhIMjFDMjEuNTUyMyAyOCAyMiAyNy41NTIzIDIyIDI3WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg==";
let removeStartListener = null;
let removeFinishListener = null;

function openAdminSidebar() {
    adminSidebarOpen.value = true;
}

function openPublicSidebar() {
    publicSidebarOpen.value = true;
}

const breadcrumbs = computed(() => {
    if (page.url.startsWith("/admin/barang")) {
        return ["Admin", "Kelola Data"];
    }

    if (page.url.startsWith("/admin/history")) {
        return ["Admin", "History"];
    }

    if (page.url.startsWith("/admin/users")) {
        return ["Admin", "Kelola User"];
    }

    if (page.url.startsWith("/admin/foto")) {
        return ["Admin", "Upload Foto"];
    }

    if (page.url === "/admin") {
        return ["Admin", "Dashboard"];
    }

    if (page.url.startsWith("/lapor-temuan")) {
        return ["Modul QR", "Lapor Barang"];
    }

    if (page.url.startsWith("/cari")) {
        return ["Publik", "Cari Barang"];
    }

    if (page.url.startsWith("/barang/")) {
        return ["Publik", "Detail Barang"];
    }

    return ["Publik", "Beranda"];
});

const adminPageTitle = computed(() => {
    if (page.url.startsWith("/admin/barang")) return "Kelola Barang";
    if (page.url.startsWith("/admin/history")) return "History";
    if (page.url.startsWith("/admin/aktivitas")) return "Log Aktivitas";
    if (page.url.startsWith("/admin/users")) return "Kelola User";
    if (page.url.startsWith("/admin/foto")) return "Upload Foto";
    if (page.url === "/admin") return "Beranda";
    return props.title;
});

const adminDateLabel = computed(() => {
    const now = new Date();
    const options = {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    };
    return now.toLocaleDateString("id-ID", options).replace(",", ",") + " WIB";
});

function isActive(path) {
    if (path === "/") {
        return page.url === "/";
    }

    if (path === "/cari") {
        return page.url.startsWith("/cari") || page.url.startsWith("/barang");
    }

    if (path === "/admin") {
        return page.url === "/admin";
    }

    return page.url === path || page.url.startsWith(`${path}/`);
}

onMounted(() => {
    removeStartListener = router.on("start", () => {
        isLoading.value = true;
    });

    removeFinishListener = router.on("finish", () => {
        isLoading.value = false;
        adminSidebarOpen.value = false;
    });

    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) {
                toast.success("Berhasil", { description: flash.success });
            }
            if (flash?.error) {
                toast.error("Ada yang perlu dicek", {
                    description: flash.error,
                });
            }
        },
        { deep: true },
    );

    const initialFlash = page.props.flash;
    if (initialFlash?.success) {
        toast.success("Berhasil", { description: initialFlash.success });
    }
    if (initialFlash?.error) {
        toast.error("Ada yang perlu dicek", {
            description: initialFlash.error,
        });
    }

    window.addEventListener("sipb:open-admin-sidebar", openAdminSidebar);
});

onBeforeUnmount(() => {
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
    window.removeEventListener("sipb:open-admin-sidebar", openAdminSidebar);
});
</script>

<template>
    <div class="min-h-screen overflow-x-hidden bg-[#f8f9fd] text-[#1a2134]">
        <aside
            v-if="admin"
            class="fixed left-0 top-0 z-20 hidden h-full w-[230px] flex-col bg-white lg:flex"
        >
            <!-- Logo -->
            <Link
                href="/"
                class="flex flex-col items-center justify-center px-4 pb-2 pt-5"
            >
                <img
                    :src="campusLogo"
                    alt="Universitas Yatsi Madani"
                    class="h-36 w-36 object-contain"
                />
            </Link>

            <!-- User Profile Box -->
            <div class="px-4 pb-4 relative">
                <button
                    type="button"
                    class="w-full text-left rounded-lg bg-[#2737c9] px-4 py-4 text-white shadow-[0_10px_24px_rgba(39,55,201,0.18)] transition-all hover:bg-[#202da8]"
                    @click="profileDropdownOpen = !profileDropdownOpen"
                >
                    <div class="flex items-center gap-3">
                        <img
                            :src="defaultProfileFoto"
                            alt="Profile"
                            class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-white/30 bg-white/20"
                        />
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-extrabold leading-tight uppercase"
                            >
                                {{ page.props.auth?.user?.name || "Admin" }}
                            </p>
                            <p class="text-xs font-medium text-white/75">
                                {{
                                    page.props.auth?.user?.is_super_admin
                                        ? "Super Admin"
                                        : "Admin"
                                }}
                            </p>
                        </div>
                        <ChevronDown
                            class="ml-auto h-4 w-4 shrink-0 text-white/80 transition-transform"
                            :class="{ 'rotate-180': profileDropdownOpen }"
                        />
                    </div>
                </button>

                <!-- Dropdown Menu -->
                <Transition name="sipb-fade">
                    <div
                        v-if="profileDropdownOpen"
                        class="absolute left-4 right-4 top-full z-50 mt-1 rounded-md bg-white py-1 shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-[#e6e9ed]"
                    >
                        <Link
                            href="/admin/profile"
                            class="flex items-center gap-3 px-4 py-3 text-[13px] font-semibold text-[#1a2134] transition-colors hover:bg-[#f6f7fa] hover:text-[#2737c9]"
                            @click="profileDropdownOpen = false"
                        >
                            <User class="h-4 w-4 text-[#747a8b]" />
                            Profil Saya
                        </Link>

                        <div class="px-3 py-2">
                            <Link
                                v-if="page.props.auth?.user"
                                href="/admin/logout"
                                method="post"
                                as="button"
                                class="flex w-full items-center justify-center rounded-md bg-[#f6f7fa] py-2.5 text-sm font-bold text-[#d93c3c] transition-colors hover:bg-[#fce8e8]"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Nav Items -->
            <nav class="min-h-0 flex-1 overflow-y-auto px-4 text-[13px]">
                <Link
                    v-for="item in adminNavItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'group relative mb-0.5 flex h-11 items-center gap-3 rounded-lg px-3 font-semibold transition-colors',
                        isActive(item.href)
                            ? 'text-[#2737c9]'
                            : 'text-[#64748b] hover:bg-[#f6f7fa] hover:text-[#2737c9]',
                    ]"
                >
                    <!-- Active indicator bar -->
                    <span
                        v-if="isActive(item.href)"
                        class="absolute right-0 top-1/2 h-6 w-[3px] -translate-y-1/2 rounded-l-full bg-[#2737c9]"
                    ></span>
                    <!-- Icon with colored bg when active -->
                    <span
                        :class="[
                            'grid h-8 w-8 shrink-0 place-items-center rounded-lg transition-colors',
                            isActive(item.href)
                                ? 'bg-[#2737c9] text-white'
                                : 'text-[#9da3b1] group-hover:text-[#2737c9]',
                        ]"
                    >
                        <component :is="item.icon" class="h-[17px] w-[17px]" />
                    </span>
                    <span class="min-w-0 flex-1 truncate">{{
                        item.label
                    }}</span>
                    <span
                        v-if="item.badge"
                        class="grid h-5 min-w-5 place-items-center rounded-full bg-[#d93c3c] px-1.5 text-[10px] font-extrabold text-white"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
            </nav>
        </aside>

        <aside
            v-if="hasPublicSidebar"
            :class="[
                'fixed bottom-0 left-0 top-0 z-20 hidden overflow-hidden border-r border-[#e6e9ed] bg-white pt-5 shadow-[18px_0_32px_rgba(26,33,52,0.04)] transition-[width] duration-200 md:block',
                publicSidebarOpen ? 'w-[220px]' : 'w-14',
            ]"
            @mouseenter="publicSidebarOpen = true"
            @mouseleave="publicSidebarOpen = false"
        >
            <Link
                href="/"
                :class="[
                    'mx-3 mb-5 flex h-10 items-center rounded-md transition-colors hover:bg-[#f6f7fa]',
                    publicSidebarOpen ? 'gap-3 px-3' : 'justify-center',
                ]"
                title="Beranda"
            >
                <img
                    :src="campusLogo"
                    alt="Universitas Yatsi Madani"
                    class="h-8 w-8 shrink-0 object-contain"
                />
                <span
                    v-if="publicSidebarOpen"
                    class="whitespace-nowrap text-sm font-extrabold text-[#1a2134]"
                    >SIPB</span
                >
            </Link>
            <nav class="grid gap-3 px-3">
                <Link
                    v-for="item in publicNavItems"
                    :key="item.href"
                    :href="item.href"
                    :class="[
                        'flex h-10 items-center rounded-md transition-colors',
                        publicSidebarOpen
                            ? 'w-full gap-3 px-3'
                            : 'w-9 justify-center',
                        isActive(item.href)
                            ? 'bg-[#dfe7ff] text-[#2737c9]'
                            : 'text-[#747a8b] hover:bg-[#f6f7fa] hover:text-[#2737c9]',
                    ]"
                    :title="item.title"
                >
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    <span
                        v-if="publicSidebarOpen"
                        class="whitespace-nowrap text-sm font-semibold"
                        >{{ item.label }}</span
                    >
                </Link>
                <Link
                    v-if="page.props.auth?.user"
                    href="/admin"
                    :class="[
                        'flex h-10 items-center rounded-md transition-colors text-[#747a8b] hover:bg-[#f6f7fa] hover:text-[#2737c9]',
                        publicSidebarOpen
                            ? 'w-full gap-3 px-3'
                            : 'w-9 justify-center',
                    ]"
                    title="Dashboard Admin"
                >
                    <LayoutDashboard class="h-5 w-5 shrink-0" />
                    <span
                        v-if="publicSidebarOpen"
                        class="whitespace-nowrap text-sm font-semibold"
                        >Admin</span
                    >
                </Link>
            </nav>
        </aside>

        <Transition name="sipb-fade">
            <div
                v-if="hasPublicSidebar && publicSidebarOpen"
                class="fixed inset-0 z-40 bg-[#1a2134]/40 md:hidden"
                @click.self="publicSidebarOpen = false"
            >
                <aside
                    class="h-full w-[280px] max-w-[82vw] bg-white px-5 py-5 shadow-[20px_0_48px_rgba(26,33,52,0.22)]"
                >
                    <div class="mb-6 flex items-center justify-between gap-3">
                        <Link href="/" class="flex items-center gap-2">
                            <span
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-[#edf2ff]"
                            >
                                <img
                                    :src="campusLogo"
                                    alt="Logo SIPB"
                                    class="h-8 w-8 object-contain"
                                />
                            </span>
                            <span class="font-extrabold text-[#1a2134]"
                                >SIPB</span
                            >
                        </Link>
                        <button
                            type="button"
                            class="sipb-icon-button"
                            title="Tutup menu"
                            @click="publicSidebarOpen = false"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <nav class="space-y-2">
                        <Link
                            v-for="item in publicNavItems"
                            :key="`public-drawer-${item.href}`"
                            :href="item.href"
                            :class="[
                                'flex h-11 items-center gap-3 rounded-md px-3 text-sm font-semibold',
                                isActive(item.href)
                                    ? 'bg-[#dfe7ff] text-[#2737c9]'
                                    : 'text-[#747a8b] hover:bg-[#f6f7fa] hover:text-[#2737c9]',
                            ]"
                        >
                            <component :is="item.icon" class="h-5 w-5" />
                            <span class="min-w-0 flex-1 truncate">{{
                                item.label
                            }}</span>
                        </Link>
                        <Link
                            v-if="page.props.auth?.user"
                            href="/admin"
                            class="flex h-11 items-center gap-3 rounded-md px-3 text-sm font-semibold text-[#747a8b] hover:bg-[#f6f7fa] hover:text-[#2737c9]"
                        >
                            <LayoutDashboard class="h-5 w-5" />
                            <span class="min-w-0 flex-1 truncate">Admin</span>
                        </Link>
                    </nav>
                </aside>
            </div>
        </Transition>

        <Transition name="sipb-fade">
            <div
                v-if="admin && adminSidebarOpen"
                class="fixed inset-0 z-40 bg-[#1a2134]/40 lg:hidden"
                @click.self="adminSidebarOpen = false"
            >
                <aside
                    class="h-full w-[280px] max-w-[82vw] bg-white shadow-[20px_0_48px_rgba(26,33,52,0.22)]"
                >
                    <!-- Profile + Close -->
                    <div
                        class="flex items-center justify-between gap-3 px-5 pb-4 pt-5"
                    >
                        <Link
                            href="/admin/profile"
                            class="flex min-w-0 flex-1 items-center gap-3"
                            @click="adminSidebarOpen = false"
                        >
                            <img
                                :src="defaultProfileFoto"
                                alt="Profile"
                                class="h-10 w-10 shrink-0 rounded-full object-cover ring-2 ring-[#2737c9]/10 bg-[#2737c9]/10"
                            />
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-extrabold text-[#1a2134]"
                                >
                                    {{ page.props.auth?.user?.name || "Admin" }}
                                </p>
                                <p class="text-xs font-medium text-[#747a8b]">
                                    {{
                                        page.props.auth?.user?.is_super_admin
                                            ? "Super Admin"
                                            : "Admin"
                                    }}
                                </p>
                            </div>
                        </Link>
                        <button
                            type="button"
                            class="sipb-icon-button"
                            title="Tutup menu"
                            @click="adminSidebarOpen = false"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <nav class="space-y-0.5 px-4">
                        <Link
                            v-for="item in adminNavItems"
                            :key="`drawer-${item.href}`"
                            :href="item.href"
                            :class="[
                                'group relative flex h-12 items-center gap-3 rounded-lg px-3 text-sm font-semibold transition-colors',
                                isActive(item.href)
                                    ? 'text-[#2737c9]'
                                    : 'text-[#64748b] hover:bg-[#f6f7fa] hover:text-[#2737c9]',
                            ]"
                        >
                            <span
                                v-if="isActive(item.href)"
                                class="absolute right-0 top-1/2 h-6 w-[3px] -translate-y-1/2 rounded-l-full bg-[#2737c9]"
                            ></span>
                            <span
                                :class="[
                                    'grid h-8 w-8 shrink-0 place-items-center rounded-lg transition-colors',
                                    isActive(item.href)
                                        ? 'bg-[#2737c9] text-white'
                                        : 'text-[#9da3b1] group-hover:text-[#2737c9]',
                                ]"
                            >
                                <component
                                    :is="item.icon"
                                    class="h-[17px] w-[17px]"
                                />
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{
                                item.label
                            }}</span>
                            <span
                                v-if="item.badge"
                                class="grid h-5 min-w-5 place-items-center rounded-full bg-[#d93c3c] px-1.5 text-[10px] font-extrabold text-white"
                            >
                                {{ item.badge }}
                            </span>
                        </Link>
                    </nav>
                </aside>
            </div>
        </Transition>

        <main
            :class="[
                'px-4 py-6 sm:px-6 md:py-[35px]',
                admin
                    ? 'lg:ml-[230px] lg:w-[calc(100%-230px)] lg:px-8 xl:px-10'
                    : [
                          'w-full max-w-none lg:px-10 xl:px-12',
                          hasPublicSidebar
                              ? 'md:ml-14 md:w-[calc(100%-56px)]'
                              : '',
                      ],
            ]"
        >
            <div
                v-if="admin && !isAdminDashboard"
                class="mb-6 flex items-start justify-between gap-4 lg:hidden"
            >
                <Link href="/admin" class="flex min-w-0 items-center gap-3">
                    <img
                        :src="campusLogo"
                        alt="Universitas Yatsi Madani"
                        class="h-14 w-14 shrink-0 object-contain"
                    />
                    <span class="min-w-0">
                        <span
                            class="block truncate text-base font-extrabold text-[#1a2134]"
                            >SIPB</span
                        >
                        <span
                            class="mt-0.5 block truncate text-xs font-bold text-[#747a8b]"
                            >Universitas Yatsi Madani</span
                        >
                    </span>
                </Link>
                <button
                    type="button"
                    class="relative mt-1 grid h-[42px] w-[42px] shrink-0 place-items-center rounded-full"
                    title="Buka menu"
                    @click="adminSidebarOpen = true"
                >
                    <img
                        :src="mobileMenuIcon"
                        alt=""
                        class="h-[42px] w-[42px]"
                    />
                </button>
            </div>

            <div
                v-if="!admin && !isPublicHome"
                class="mb-6 flex items-start justify-between gap-4 md:hidden"
            >
                <Link href="/" class="flex min-w-0 items-center gap-3">
                    <img
                        :src="campusLogo"
                        alt="Universitas Yatsi Madani"
                        class="h-14 w-14 shrink-0 object-contain"
                    />
                    <span class="min-w-0">
                        <span
                            class="block truncate text-base font-extrabold text-[#1a2134]"
                            >SIPB</span
                        >
                        <span
                            class="mt-0.5 block truncate text-xs font-bold text-[#747a8b]"
                            >Universitas Yatsi Madani</span
                        >
                    </span>
                </Link>
                <button
                    v-if="hasPublicSidebar"
                    type="button"
                    class="mt-1 grid h-[42px] w-[42px] shrink-0 place-items-center rounded-full"
                    title="Buka menu"
                    @click="openPublicSidebar"
                >
                    <img
                        :src="mobileMenuIcon"
                        alt=""
                        class="h-[42px] w-[42px]"
                    />
                </button>
            </div>

            <!-- Admin page header with breadcrumb + time (non-dashboard pages) -->
            <div v-if="admin && !isAdminDashboard" class="mb-6 hidden lg:block">
                <!-- Page Header -->
                <div class="mb-5 flex items-center justify-between gap-4">
                    <h1 class="text-2xl font-extrabold text-[#071735]">
                        {{ adminPageTitle }}
                    </h1>
                    <div class="flex items-center gap-4">
                        <p class="text-sm font-semibold text-[#747a8b]">
                            {{ adminDateLabel }}
                        </p>
                        <div class="flex items-center gap-2">
                            <img
                                :src="civitasLogo"
                                alt=""
                                class="h-12 w-12 object-contain"
                            />
                        </div>
                    </div>
                </div>

                <!-- Breadcrumb Bar (Distinct gray background) -->
                <div class="-mx-8 bg-[#eaeff5] px-8 py-3 xl:-mx-10 xl:px-10">
                    <div
                        class="flex items-center gap-2 text-[13px] font-semibold"
                    >
                        <Link
                            href="/admin"
                            class="text-[#1a2134] hover:text-[#2737c9]"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-[15px] w-[15px]"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
                                />
                            </svg>
                        </Link>
                        <ChevronRight
                            class="h-3.5 w-3.5 text-[#94a3b8]"
                            stroke-width="2.5"
                        />
                        <span class="text-[#747a8b]">{{ adminPageTitle }}</span>
                    </div>
                </div>
            </div>

            <slot />
        </main>

        <Toaster position="top-right" richColors />

        <Transition name="sipb-fade">
            <div
                v-if="isLoading"
                class="fixed left-0 right-0 top-0 z-[70] bg-[#dfe7ff]"
            >
                <div
                    class="sipb-progress-bar !h-1 !rounded-none !bg-transparent"
                >
                    <span></span>
                </div>
            </div>
        </Transition>

        <AiChatWidget v-if="showAiAssistant" />
    </div>
</template>
