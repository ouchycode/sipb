<script setup>
import { router, useForm } from "@inertiajs/vue3";
import {
    Edit3,
    Filter,
    Mail,
    PlusCircle,
    Save,
    Search,
    ShieldCheck,
    Trash2,
    UserRound,
    UsersRound,
    X,
} from "@lucide/vue";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import ActiveFilters from "../../Shared/ActiveFilters.vue";
import AppLayout from "../../Shared/AppLayout.vue";
import FilterDrawer from "../../Shared/FilterDrawer.vue";
import Pagination from "../../Shared/Pagination.vue";
import SearchToolbar from "../../Shared/SearchToolbar.vue";
import { formatDate } from "../../Shared/status";
import Swal from "sweetalert2";

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    stats: Object,
});

const pageLoading = ref(false);
let removePageStartListener = null;
let removePageFinishListener = null;

const userRows = computed(() => props.users.data ?? props.users);
const filters = reactive({
    q: props.filters.q ?? "",
    role: props.filters.role ?? "",
    per_page: props.filters.per_page ?? 10,
});
const skeletonRows = computed(() => Math.min(Number(filters.per_page) || 10, 10));
const isDenseTable = computed(() => Number(filters.per_page) >= 20);
const isVeryDenseTable = computed(() => Number(filters.per_page) >= 50);

const showCreateForm = ref(false);
const showAdvancedFilters = ref(false);
const editingUser = ref(null);

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

const createForm = useForm({
    name: "",
    username: "",
    email: "",
    role: "admin",
    password: "",
});

const editForm = useForm({
    name: "",
    username: "",
    email: "",
    role: "admin",
    password: "",
});

const activeFilters = computed(() =>
    [
        filters.q ? { key: "q", label: `Keyword: ${filters.q}` } : null,
        filters.role
            ? { key: "role", label: `Role: ${roleLabel(filters.role)}` }
            : null,
    ].filter(Boolean),
);

function roleLabel(role) {
    return props.roles.find((item) => item.value === role)?.label ?? "Admin";
}

function applyFilters() {
    router.get("/admin/users", filters, {
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
    filters.role = "";
    applyFilters();
}

function closeCreateForm() {
    createForm.reset();
    createForm.clearErrors();
    showCreateForm.value = false;
}

function submitCreate() {
    createForm.clearErrors();

    let hasError = false;
    if (!createForm.name) {
        createForm.setError("name", "Nama wajib diisi.");
        hasError = true;
    }
    if (!createForm.email) {
        createForm.setError("email", "Email wajib diisi.");
        hasError = true;
    }
    if (!createForm.password || createForm.password.length < 8) {
        createForm.setError("password", "Password minimal 8 karakter.");
        hasError = true;
    }
    if (hasError) return;

    createForm.post("/admin/users", {
        preserveScroll: true,
        onSuccess: closeCreateForm,
    });
}

function openEditForm(user) {
    editingUser.value = user;
    editForm.defaults({
        name: user.name,
        username: user.username ?? "",
        email: user.email,
        role: user.role,
        password: "",
    });
    editForm.reset();
    editForm.clearErrors();
}

function closeEditForm() {
    editingUser.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function submitEdit() {
    editForm.clearErrors();

    let hasError = false;
    if (!editForm.name) {
        editForm.setError("name", "Nama wajib diisi.");
        hasError = true;
    }
    if (!editForm.email) {
        editForm.setError("email", "Email wajib diisi.");
        hasError = true;
    }
    if (hasError) return;

    editForm.patch(`/admin/users/${editingUser.value.id}`, {
        preserveScroll: true,
        onSuccess: closeEditForm,
    });
}

function deleteUser(user) {
    Swal.fire({
        title: "Hapus Akun?",
        text: `Apakah Anda yakin ingin menghapus akun "${user.name}"? Data yang dihapus tidak dapat dikembalikan.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d93c3c",
        cancelButtonColor: "#747a8b",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/users/${user.id}`, {
                preserveScroll: true,
            });
        }
    });
}
</script>

<template>
    <AppLayout title="Kelola User" admin>
        <section
            class="mb-7 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex-1">
                <p class="text-sm font-semibold text-[#747a8b]">
                    Khusus super admin untuk melihat dan mengatur akun admin
                    SIPB.
                </p>
            </div>
        </section>

        <section class="mb-6 grid gap-3 md:grid-cols-3">
            <article class="sipb-panel flex items-center justify-between p-4">
                <div>
                    <p class="text-sm font-bold text-[#747a8b]">Total akun</p>
                    <p class="mt-2 text-2xl font-extrabold text-[#071735]">
                        {{ stats.total }}
                    </p>
                </div>
                <span
                    class="grid h-10 w-10 place-items-center rounded-md bg-[#2737c9] text-white"
                >
                    <UsersRound class="h-5 w-5" />
                </span>
            </article>
            <article class="sipb-panel flex items-center justify-between p-4">
                <div>
                    <p class="text-sm font-bold text-[#747a8b]">Admin</p>
                    <p class="mt-2 text-2xl font-extrabold text-[#071735]">
                        {{ stats.admin }}
                    </p>
                </div>
                <span
                    class="grid h-10 w-10 place-items-center rounded-md bg-[#00bf8e] text-white"
                >
                    <UserRound class="h-5 w-5" />
                </span>
            </article>
            <article class="sipb-panel flex items-center justify-between p-4">
                <div>
                    <p class="text-sm font-bold text-[#747a8b]">Super admin</p>
                    <p class="mt-2 text-2xl font-extrabold text-[#071735]">
                        {{ stats.super_admin }}
                    </p>
                </div>
                <span
                    class="grid h-10 w-10 place-items-center rounded-md bg-[#feae37] text-white"
                >
                    <ShieldCheck class="h-5 w-5" />
                </span>
            </article>
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
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md bg-[#2737c9] px-4 py-1.5 text-sm font-bold text-white transition-colors hover:bg-[#1f2dad]"
                    @click="showCreateForm = true"
                >
                    <PlusCircle class="h-4 w-4" /> Tambah
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
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]">Role</span>
                <select
                    v-model="filters.role"
                    class="w-full rounded-md border border-[#e2e8f0] px-3 py-2.5 text-sm focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]"
                >
                    <option value="">Semua role</option>
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                    >
                        {{ role.label }}
                    </option>
                </select>
            </label>
        </FilterDrawer>

        <section class="overflow-hidden">
            <div class="grid gap-3 p-4 md:hidden">
                <template v-if="pageLoading">
                    <article v-for="index in skeletonRows" :key="`user-mobile-skeleton-${index}`" class="rounded-md sipb-panel p-4">
                        <div class="flex items-start gap-3">
                            <span class="sipb-skeleton h-10 w-10 shrink-0 rounded-md"></span>
                            <div class="min-w-0 flex-1 space-y-2">
                                <span class="sipb-skeleton h-4 w-3/4"></span>
                                <span class="sipb-skeleton h-3 w-1/2"></span>
                                <span class="sipb-skeleton h-5 w-20 rounded-md"></span>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end gap-2">
                            <span class="sipb-skeleton h-9 w-20 rounded-md"></span>
                            <span class="sipb-skeleton h-9 w-9 rounded-md"></span>
                        </div>
                    </article>
                </template>
                <div
                    v-else-if="userRows.length === 0"
                    class="rounded-md bg-[#f6f7fa] p-5 text-center text-sm font-medium text-[#747a8b]"
                >
                    Belum ada akun sesuai filter.
                </div>
                <article
                    v-for="user in userRows"
                    :key="`user-card-${user.id}`"
                    class="rounded-md sipb-panel p-4"
                >
                    <div class="flex items-start gap-3">
                        <img
                            :src="'/assets/profile_foto.png'"
                            :alt="'Foto profil ' + user.name"
                            class="h-10 w-10 shrink-0 rounded-md object-cover bg-white ring-1 ring-[#e2e8f0]"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-extrabold text-[#1a2134]">
                                {{ user.name }}
                            </p>
                            <p
                                class="mt-1 truncate text-sm font-semibold text-[#747a8b]"
                            >
                                {{ user.email }}
                            </p>
                            <p
                                v-if="user.username"
                                class="truncate text-xs font-medium text-[#747a8b]"
                            >
                                @{{ user.username }}
                            </p>
                            <span
                                class="mt-2 inline-flex rounded-md border border-[#dfe7ff] bg-[#edf2ff] px-2.5 py-1 text-xs font-bold text-[#2737c9]"
                            >
                                {{ user.role_label }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button
                            type="button"
                            class="sipb-button-secondary inline-flex flex-1 items-center justify-center gap-2"
                            @click="openEditForm(user)"
                        >
                            <Edit3 class="h-4 w-4" />
                            Edit
                        </button>
                        <button
                            type="button"
                            :disabled="user.is_current_user"
                            class="sipb-icon-button text-[#d93c3c] hover:text-[#d93c3c] disabled:opacity-40"
                            title="Hapus akun"
                            @click="deleteUser(user)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>
                </article>
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="border-b border-[#e2e8f0] text-left">
                        <tr>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                No
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Akun
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Role
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Dibuat
                            </th>
                            <th class="px-4 py-3 font-bold text-[#1a2134]">
                                Update terakhir
                            </th>
                            <th
                                class="px-4 py-3 text-right font-bold text-[#1a2134]"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="pageLoading">
                            <tr v-for="index in skeletonRows" :key="`user-skeleton-${index}`" class="align-middle">
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-8"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                    <div class="flex items-center gap-3">
                                        <span class="sipb-skeleton h-10 w-10 shrink-0 rounded-md"></span>
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <span class="sipb-skeleton h-4 w-32"></span>
                                            <span class="sipb-skeleton h-3 w-44"></span>
                                        </div>
                                    </div>
                                </td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-6 w-16"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-24"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><span class="sipb-skeleton h-4 w-24"></span></td>
                                <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']"><div class="flex justify-end gap-2"><span class="sipb-skeleton h-8 w-8"></span><span class="sipb-skeleton h-8 w-8"></span></div></td>
                            </tr>
                        </template>
                        <tr
                            v-else-if="userRows.length === 0"
                            class="border-b border-[#f1f5f9] bg-white"
                        >
                            <td
                                colspan="6"
                                class="px-4 py-8 text-center text-[#747a8b]"
                            >
                                Belum ada akun sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="(user, index) in userRows"
                            :key="user.id"
                            class="border-b border-[#f1f5f9] align-middle odd:bg-white even:bg-[#f8f9fd] hover:bg-[#f1f5f9]"
                        >
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3', 'text-[#1a2134]']">
                                {{
                                    (props.users.current_page - 1) *
                                        props.users.per_page +
                                    index +
                                    1
                                }}
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <div class="flex min-w-0 items-center gap-3">
                                    <img
                                        :src="'/assets/profile_foto.png'"
                                        :alt="'Foto profil ' + user.name"
                                        :class="[isDenseTable ? 'h-8 w-8' : 'h-10 w-10', 'shrink-0 rounded-md object-cover bg-white ring-1 ring-[#e2e8f0]']"
                                    />
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-bold text-[#1a2134]"
                                        >
                                            {{ user.name }}
                                            <span
                                                v-if="user.is_current_user"
                                                class="ml-2 text-xs font-bold text-[#00a676]"
                                                >Anda</span
                                            >
                                        </p>
                                        <p
                                            class="mt-1 flex items-center gap-1 truncate text-xs font-semibold text-[#747a8b]"
                                        >
                                            <Mail class="h-3.5 w-3.5" />
                                            {{ user.email }}
                                        </p>
                                        <p
                                            v-if="user.username"
                                            class="mt-0.5 text-xs font-medium text-[#747a8b]"
                                        >
                                            @{{ user.username }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <span
                                    class="inline-flex rounded-md border border-[#dfe7ff] bg-[#edf2ff] px-2.5 py-1 text-xs font-bold text-[#2737c9]"
                                >
                                    {{ user.role_label }}
                                </span>
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3', 'font-semibold text-[#747a8b]']">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3', 'font-semibold text-[#747a8b]']">
                                {{ formatDate(user.updated_at) }}
                            </td>
                            <td :class="[isDenseTable ? 'px-3 py-2' : 'px-4 py-3']">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        :class="['sipb-icon-button', isVeryDenseTable ? '!h-7 !w-7' : '!h-8 !w-8']"
                                        title="Edit akun"
                                        @click="openEditForm(user)"
                                    >
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        :disabled="user.is_current_user"
                                        :class="['sipb-icon-button text-[#d93c3c] hover:text-[#d93c3c] disabled:opacity-40', isVeryDenseTable ? '!h-7 !w-7' : '!h-8 !w-8']"
                                        title="Hapus akun"
                                        @click="deleteUser(user)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                <Pagination
                    :meta="users"
                    @update:perPage="
                        (val) => {
                            filters.per_page = val;
                            applyFilters();
                        }
                    "
                />
            </div>
        </section>

        <div
            v-if="showCreateForm"
            class="sipb-modal-backdrop"
            @click.self="closeCreateForm"
        >
            <section class="sipb-modal-card max-w-[560px]">
                <div
                    class="flex items-start justify-between gap-4 border-b border-[#e6e9ed] px-5 py-4"
                >
                    <div>
                        <h2 class="sipb-section-title flex items-center gap-2">
                            <PlusCircle class="h-4 w-4 text-[#2737c9]" />
                            Tambah akun admin
                        </h2>
                        <p class="sipb-muted mt-1 text-sm">
                            Akun ini bisa masuk ke dashboard admin SIPB.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sipb-icon-button"
                        title="Tutup"
                        @click="closeCreateForm"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <form class="grid gap-4 p-5" @submit.prevent="submitCreate">
                    <label class="block">
                        <span class="sipb-label">Nama</span>
                        <input
                            v-model="createForm.name"
                            class="sipb-input"
                            placeholder="Nama admin"
                        />
                        <p v-if="createForm.errors.name" class="sipb-error">
                            {{ createForm.errors.name }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Username</span>
                        <input
                            v-model="createForm.username"
                            class="sipb-input"
                            placeholder="Opsional, untuk login alternatif"
                        />
                        <p v-if="createForm.errors.username" class="sipb-error">
                            {{ createForm.errors.username }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Email</span>
                        <input
                            v-model="createForm.email"
                            type="email"
                            class="sipb-input"
                            placeholder="admin@kampus.ac.id"
                        />
                        <p v-if="createForm.errors.email" class="sipb-error">
                            {{ createForm.errors.email }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Role</span>
                        <select v-model="createForm.role" class="sipb-input">
                            <option
                                v-for="role in roles"
                                :key="`create-${role.value}`"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <p v-if="createForm.errors.role" class="sipb-error">
                            {{ createForm.errors.role }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Password awal</span>
                        <input
                            v-model="createForm.password"
                            type="password"
                            class="sipb-input"
                            placeholder="Minimal 8 karakter"
                        />
                        <p v-if="createForm.errors.password" class="sipb-error">
                            {{ createForm.errors.password }}
                        </p>
                    </label>
                    <div
                        class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            class="sipb-button-secondary"
                            @click="closeCreateForm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="sipb-button-primary inline-flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <Save class="h-4 w-4" />
                            Simpan akun
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <div
            v-if="editingUser"
            class="sipb-modal-backdrop"
            @click.self="closeEditForm"
        >
            <section class="sipb-modal-card max-w-[560px]">
                <div
                    class="flex items-start justify-between gap-4 border-b border-[#e6e9ed] px-5 py-4"
                >
                    <div>
                        <h2 class="sipb-section-title flex items-center gap-2">
                            <Edit3 class="h-4 w-4 text-[#2737c9]" />
                            Edit akun admin
                        </h2>
                        <p class="sipb-muted mt-1 text-sm">
                            Kosongkan password jika tidak ingin diganti.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sipb-icon-button"
                        title="Tutup"
                        @click="closeEditForm"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <form class="grid gap-4 p-5" @submit.prevent="submitEdit">
                    <label class="block">
                        <span class="sipb-label">Nama</span>
                        <input v-model="editForm.name" class="sipb-input" />
                        <p v-if="editForm.errors.name" class="sipb-error">
                            {{ editForm.errors.name }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Username</span>
                        <input
                            v-model="editForm.username"
                            class="sipb-input"
                            placeholder="Opsional, untuk login alternatif"
                        />
                        <p v-if="editForm.errors.username" class="sipb-error">
                            {{ editForm.errors.username }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Email</span>
                        <input
                            v-model="editForm.email"
                            type="email"
                            class="sipb-input"
                        />
                        <p v-if="editForm.errors.email" class="sipb-error">
                            {{ editForm.errors.email }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Role</span>
                        <select v-model="editForm.role" class="sipb-input">
                            <option
                                v-for="role in roles"
                                :key="`edit-${role.value}`"
                                :value="role.value"
                            >
                                {{ role.label }}
                            </option>
                        </select>
                        <p v-if="editForm.errors.role" class="sipb-error">
                            {{ editForm.errors.role }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Password baru</span>
                        <input
                            v-model="editForm.password"
                            type="password"
                            class="sipb-input"
                            placeholder="Opsional"
                        />
                        <p v-if="editForm.errors.password" class="sipb-error">
                            {{ editForm.errors.password }}
                        </p>
                    </label>
                    <div
                        class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            class="sipb-button-secondary"
                            @click="closeEditForm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="sipb-button-primary inline-flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <Save class="h-4 w-4" />
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
