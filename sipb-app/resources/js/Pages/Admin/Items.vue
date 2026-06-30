<script setup>
import { router, useForm } from "@inertiajs/vue3";
import {
    AlertTriangle,
    AlignLeft,
    Archive,
    CalendarDays,
    CheckCircle2,
    CheckSquare,
    ChevronDown,
    ClipboardCheck,
    Clock,
    Download,
    Edit3,
    Eye,
    FileText,
    Filter,
    Image,
    Info,
    MapPin,
    PlusCircle,
    Save,
    Search,
    Trash2,
    User,
    X,
} from "@lucide/vue";
import Swal from "sweetalert2";
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import AppLayout from "../../Shared/AppLayout.vue";
import ImagePreviewModal from "../../Shared/ImagePreviewModal.vue";
import Pagination from "../../Shared/Pagination.vue";
import ActiveFilters from "../../Shared/ActiveFilters.vue";
import FilterDrawer from "../../Shared/FilterDrawer.vue";
import SearchToolbar from "../../Shared/SearchToolbar.vue";
import { formatDate, maskNim, statusClass, statusLabel } from "../../Shared/status";

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

const showCreateForm = ref(false);
const showAdvancedFilters = ref(false);
const selectedItem = ref(null);
const previewImage = ref(null);
const editingItem = ref(null);
const statusItem = ref(null);
const statusAction = ref("");
const selectedIds = ref([]);
const photoName = ref("");
const photoPreview = ref("");
const editPhotoName = ref("");
const editPhotoPreview = ref("");
const editPhotoInput = ref(null);
const editUploadedPhotoId = ref(null);
const editPhotoDropdownOpen = ref(false);
const editPhotoDropdownButton = ref(null);
const editSelectedPhoto = computed(
    () =>
        uploadedPhotos.value.find((p) => p.id === editUploadedPhotoId.value) ??
        null,
);
const uploadedPhotos = ref([]);
const selectedUploadedPhotoId = ref(null);
const photoDropdownOpen = ref(false);
const photoDropdownButton = ref(null);
const selectedPhoto = computed(
    () =>
        uploadedPhotos.value.find(
            (p) => p.id === selectedUploadedPhotoId.value,
        ) ?? null,
);
const fallbackImage = "/assets/logo-uym.png";
const maxPhotoSize = 4 * 1024 * 1024;
const compressionThreshold = 1.5 * 1024 * 1024;

async function fetchUploadedPhotos() {
    try {
        const res = await fetch("/admin/uploaded-photos?unused=1");
        if (res.ok) {
            uploadedPhotos.value = await res.json();
        }
    } catch (err) {
        console.warn('Failed to fetch uploaded photos:', err);
    }
}

function selectUploadedPhoto(photo) {
    selectedUploadedPhotoId.value =
        selectedUploadedPhotoId.value === photo.id ? null : photo.id;
    form.uploaded_photo_id = selectedUploadedPhotoId.value;
    if (selectedUploadedPhotoId.value) {
        form.photo = null;
        photoPreview.value = photo.photo_url;
        photoName.value = "Dari galeri upload";
    } else {
        photoPreview.value = "";
        photoName.value = "";
    }
    form.clearErrors("photo");
    photoDropdownOpen.value = false;
}

function toggleDropdown() {
    photoDropdownOpen.value = !photoDropdownOpen.value;
    if (photoDropdownOpen.value) fetchUploadedPhotos();
}

function handlePhotoDropdownClickOutside(event) {
    if (
        photoDropdownOpen.value &&
        photoDropdownButton.value &&
        !photoDropdownButton.value.contains(event.target)
    ) {
        photoDropdownOpen.value = false;
    }
    if (
        editPhotoDropdownOpen.value &&
        editPhotoDropdownButton.value &&
        !editPhotoDropdownButton.value.contains(event.target)
    ) {
        editPhotoDropdownOpen.value = false;
    }
}

function selectEditUploadedPhoto(photo) {
    editUploadedPhotoId.value =
        editUploadedPhotoId.value === photo.id ? null : photo.id;
    editForm.uploaded_photo_id = editUploadedPhotoId.value;
    if (editUploadedPhotoId.value) {
        editForm.photo = null;
        editPhotoPreview.value = photo.photo_url;
        editPhotoName.value = "Dari galeri upload";
    } else {
        editPhotoPreview.value = "";
        editPhotoName.value = "";
    }
    editForm.clearErrors("photo");
    editPhotoDropdownOpen.value = false;
}

function toggleEditDropdown() {
    editPhotoDropdownOpen.value = !editPhotoDropdownOpen.value;
    if (editPhotoDropdownOpen.value) fetchUploadedPhotos();
}

function openUploadPhotosPage() {
    window.open("/admin/foto", "_blank");
    window.addEventListener(
        "focus",
        () => {
            fetchUploadedPhotos();
        },
        { once: true },
    );
}

const form = useForm({
    name: "",
    category: props.categories[0] ?? "",
    description: "",
    location: props.locations[0] ?? "",
    found_at: "",
    photo: null,
    uploaded_photo_id: null,
    finder_name: "",
    storage_location: "Resepsionis",
    admin_notes: "",
    status: "tersedia",
});

const itemRows = computed(() => props.items.data ?? props.items);
const itemTotal = computed(() => props.items.total ?? itemRows.value.length);
const isDenseTable = computed(() => Number(filters.per_page) >= 20);
const isVeryDenseTable = computed(() => Number(filters.per_page) >= 50);
const skeletonRows = computed(() =>
    Math.min(Number(filters.per_page) || 10, 10),
);
const activeFilters = computed(() =>
    [
        filters.q ? { key: "q", label: `Keyword: ${filters.q}` } : null,
        filters.category
            ? { key: "category", label: `Kategori: ${filters.category}` }
            : null,
        filters.location
            ? { key: "location", label: `Lokasi: ${filters.location}` }
            : null,
    ].filter(Boolean),
);
const overview = computed(() => ({
    available: itemRows.value.filter((item) => item.status === "tersedia")
        .length,
}));

const editForm = useForm({
    name: "",
    category: "",
    description: "",
    location: "",
    found_at: "",
    photo: null,
    uploaded_photo_id: null,
    finder_name: "",
    finder_nim: "",
    storage_location: "Resepsionis",
    admin_notes: "",
});

const statusForm = useForm({
    status: "",
    claimant_name: "",
    claimant_nim: "",
    validation_notes: "",
    pickup_checklist: {
        identity_checked: false,
        ownership_checked: false,
        condition_checked: false,
    },
});

const bulkForm = useForm({
    ids: [],
    action: "tersedia",
});
const pageLoading = ref(false);
let removePageStartListener = null;
let removePageFinishListener = null;

const statusCopy = computed(
    () =>
        ({
            tersedia: {
                title: "Publish laporan",
                description:
                    "Barang akan tampil di halaman cari barang publik.",
                button: "Publish laporan",
                icon: CheckCircle2,
            },
            sudah_diambil: {
                title: "Bukti pengambilan",
                description:
                    "Isi data pengambil agar history pengembalian jelas.",
                button: "Tandai sudah diambil",
                icon: ClipboardCheck,
            },
        })[statusAction.value] ?? {
            title: "Ubah status",
            description: "Lengkapi catatan jika diperlukan.",
            button: "Simpan status",
            icon: Save,
        },
);

function toDateTimeLocal(value) {
    if (!value) {
        return "";
    }

    const date = new Date(value);
    const offset = date.getTimezoneOffset();
    const local = new Date(date.getTime() - offset * 60000);

    return local.toISOString().slice(0, 16);
}

function closeCreateForm() {
    removePhoto();
    selectedUploadedPhotoId.value = null;
    showCreateForm.value = false;
}

function openItemDetail(item) {
    selectedItem.value = item;
}

function firstAuditAt(item, status) {
    return (
        item.audits?.find((audit) => audit.to_status === status)?.created_at ??
        null
    );
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

function closeItemDetail() {
    selectedItem.value = null;
}

function openImagePreview(item) {
    previewImage.value = {
        src: item.photo_url,
        alt: item.name,
        title: item.name,
    };
}

function openImagePreviewSource(src, title = "Preview foto barang") {
    previewImage.value = {
        src,
        alt: title,
        title,
    };
}

function closeImagePreview() {
    previewImage.value = null;
}

function openEditForm(item) {
    editingItem.value = item;
    editForm.defaults({
        name: item.name ?? "",
        category: item.category ?? props.categories[0] ?? "",
        description: item.description ?? "",
        location: item.location ?? props.locations[0] ?? "",
        found_at: toDateTimeLocal(item.found_at),
        photo: null,
        finder_name: item.finder_name ?? "",
        storage_location: item.storage_location ?? "",
        admin_notes: item.admin_notes ?? "",
    });
    editForm.reset();
    editForm.clearErrors();
    editUploadedPhotoId.value = null;
    removeEditPhoto();
}

function deleteItem(item) {
    Swal.fire({
        title: "Hapus Laporan?",
        text: `Apakah Anda yakin ingin menghapus laporan "${item.name}"? Data yang dihapus tidak dapat dikembalikan.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d93c3c",
        cancelButtonColor: "#747a8b",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/barang/${item.id}`, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    });
}

function closeEditForm() {
    removeEditPhoto();
    editingItem.value = null;
}

function applyFilters() {
    router.get("/admin/barang", filters, {
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
    filters.category = "";
    filters.location = "";
    filters.per_page = 10;
    applyFilters();
}

function submit() {
    if (form.processing) {
        return;
    }

    form.clearErrors();

    let hasError = false;
    if (!form.name) {
        form.setError("name", "Nama barang wajib diisi.");
        hasError = true;
    }
    if (!form.description) {
        form.setError("description", "Deskripsi wajib diisi.");
        hasError = true;
    }
    if (!form.found_at) {
        form.setError("found_at", "Tanggal/jam wajib diisi.");
        hasError = true;
    }
    if (!form.uploaded_photo_id) {
        form.setError("photo", "Pilih foto dari upload terbaru.");
        hasError = true;
    }
    if (hasError) return;

    form.post("/admin/barang", {
        preserveScroll: true,
        onSuccess: () => {
            form.reset(
                "name",
                "description",
                "found_at",
                "photo",
                "uploaded_photo_id",
                "finder_name",
                "storage_location",
                "admin_notes",
            );
            photoName.value = "";
            removePhoto();
            showCreateForm.value = false;
        },
    });
}

function submitEdit() {
    if (!editingItem.value || editForm.processing) {
        return;
    }

    editForm.clearErrors();

    let hasError = false;
    if (!editForm.name) {
        editForm.setError("name", "Nama barang wajib diisi.");
        hasError = true;
    }
    if (!editForm.description) {
        editForm.setError("description", "Deskripsi wajib diisi.");
        hasError = true;
    }
    if (!editForm.found_at) {
        editForm.setError("found_at", "Tanggal/jam wajib diisi.");
        hasError = true;
    }
    if (hasError) return;

    editForm.post(`/admin/barang/${editingItem.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditForm();
            closeItemDetail();
        },
    });
}

function compressImage(file) {
    return new Promise((resolve, reject) => {
        const image = new window.Image();
        const objectUrl = URL.createObjectURL(file);

        image.onload = () => {
            const maxSide = 1600;
            const scale = Math.min(
                maxSide / image.width,
                maxSide / image.height,
                1,
            );
            const canvas = document.createElement("canvas");
            canvas.width = Math.round(image.width * scale);
            canvas.height = Math.round(image.height * scale);
            canvas
                .getContext("2d")
                .drawImage(image, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(
                (blob) => {
                    URL.revokeObjectURL(objectUrl);

                    if (!blob) {
                        reject(new Error("Gagal mengompres foto."));
                        return;
                    }

                    resolve(
                        new File(
                            [blob],
                            file.name.replace(/\.[^.]+$/, ".jpg"),
                            {
                                type: "image/jpeg",
                                lastModified: Date.now(),
                            },
                        ),
                    );
                },
                "image/jpeg",
                0.82,
            );
        };

        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error("Foto tidak bisa dibaca."));
        };

        image.src = objectUrl;
    });
}

function removePhoto() {
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }

    previewImage.value = null;
    form.photo = null;
    form.uploaded_photo_id = null;
    selectedUploadedPhotoId.value = null;
    photoName.value = "";
    photoPreview.value = "";
    form.clearErrors("photo");
}

async function selectEditPhoto(event) {
    const file = event.target.files?.[0] ?? null;
    editForm.clearErrors("photo");

    if (file && !file.type.startsWith("image/")) {
        removeEditPhoto();
        editForm.setError("photo", "File harus berupa gambar.");
        return;
    }

    if (file && file.size > maxPhotoSize) {
        removeEditPhoto();
        editForm.setError("photo", "Ukuran foto maksimal 4 MB.");
        return;
    }

    if (editPhotoPreview.value) {
        URL.revokeObjectURL(editPhotoPreview.value);
    }

    const finalFile =
        file && file.size > compressionThreshold
            ? await compressImage(file).catch(() => file)
            : file;

    editForm.photo = finalFile;
    editPhotoName.value = finalFile?.name ?? "";
    editPhotoPreview.value = finalFile ? URL.createObjectURL(finalFile) : "";
}

function removeEditPhoto() {
    if (editPhotoPreview.value) {
        URL.revokeObjectURL(editPhotoPreview.value);
    }

    previewImage.value = null;
    editForm.photo = null;
    editForm.uploaded_photo_id = null;
    editUploadedPhotoId.value = null;
    editPhotoName.value = "";
    editPhotoPreview.value = "";
    editForm.clearErrors("photo");

    if (editPhotoInput.value) {
        editPhotoInput.value.value = "";
    }
}

function openStatusModal(item, status) {
    statusItem.value = item;
    statusAction.value = status;
    statusForm.defaults({
        status,
        claimant_name: item.claimant_name ?? "",
        claimant_nim: item.claimant_nim ?? "",
        validation_notes: "",
        pickup_checklist: {
            identity_checked: false,
            ownership_checked: false,
            condition_checked: false,
        },
    });
    statusForm.reset();
    statusForm.clearErrors();
}

function closeStatusModal() {
    statusItem.value = null;
    statusAction.value = "";
}

function submitStatus() {
    if (!statusItem.value || statusForm.processing) {
        return;
    }

    statusForm.patch(`/admin/barang/${statusItem.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            closeStatusModal();
            closeItemDetail();
        },
    });
}

function toggleSelection(itemId) {
    selectedIds.value = selectedIds.value.includes(itemId)
        ? selectedIds.value.filter((id) => id !== itemId)
        : [...selectedIds.value, itemId];
}

function runBulkAction(action) {
    if (selectedIds.value.length === 0 || bulkForm.processing) {
        return;
    }

    bulkForm.ids = selectedIds.value;
    bulkForm.action = action;
    bulkForm.patch("/admin/barang/bulk", {
        preserveScroll: true,
        onSuccess: () => {
            selectedIds.value = [];
            bulkForm.reset();
        },
    });
}

function useFallbackImage(event) {
    event.target.src = fallbackImage;
}

onMounted(() => {
    fetchUploadedPhotos();
    removePageStartListener = router.on("start", (visit) => {
        pageLoading.value = true;
    });
    removePageFinishListener = router.on("finish", () => {
        pageLoading.value = false;
        fetchUploadedPhotos();
    });
    document.addEventListener("click", handlePhotoDropdownClickOutside);
});

onBeforeUnmount(() => {
    removePageStartListener?.();
    removePageFinishListener?.();
    document.removeEventListener("click", handlePhotoDropdownClickOutside);

    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
    if (editPhotoPreview.value) {
        URL.revokeObjectURL(editPhotoPreview.value);
    }
});
</script>

<template>
    <AppLayout title="Kelola data barang" admin>
        <section
            class="mb-7 flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <div class="flex-1">
                <p class="text-sm font-semibold text-[#747a8b]">
                    Review laporan aktif, publish barang, dan tandai selesai.
                </p>
            </div>
        </section>

        <section
            class="sipb-panel mb-6 flex flex-wrap items-center gap-x-5 gap-y-2 px-4 py-3 text-sm"
        >
            <span class="font-extrabold text-[#1a2134]">Ringkasan</span>

            <span class="font-semibold text-[#747a8b]"
                >Tersedia
                <strong class="text-[#1a2134]">{{
                    overview.available
                }}</strong></span
            >
        </section>

        <SearchToolbar v-model="filters.q" @search="applyFilters">
            <template #actions>
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-md border border-[#00bf8e] px-4 py-1.5 text-sm font-bold text-[#00bf8e] transition-colors hover:bg-[#00bf8e] hover:text-white"
                    @click="showAdvancedFilters = !showAdvancedFilters"
                >
                    <Filter class="h-4 w-4" /> Filter
                </button>
                <a
                    href="/admin/export-excel"
                    class="flex items-center gap-2 rounded-md border border-[#00bf8e] px-4 py-1.5 text-sm font-bold text-[#00bf8e] transition-colors hover:bg-[#00bf8e] hover:text-white"
                >
                    <Download class="h-4 w-4" /> Export
                </a>
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
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]"
                    >Kategori</span
                >
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
                <span class="mb-1.5 block text-sm font-bold text-[#1a2134]"
                    >Lokasi</span
                >
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

        <div
            v-if="showCreateForm"
            class="sipb-modal-backdrop"
            @click.self="closeCreateForm"
        >
            <section class="sipb-modal-card">
                <div
                    class="flex items-start justify-between gap-4 border-b border-[#e6e9ed] px-5 py-4"
                >
                    <div>
                        <h2 class="sipb-section-title flex items-center gap-2">
                            <PlusCircle class="h-4 w-4 text-[#2737c9]" />
                            Tambah barang
                        </h2>
                        <p class="sipb-muted mt-1 text-sm">
                            Catat laporan barang temuan atau kehilangan baru ke
                            dalam sistem.
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
                <div v-if="form.processing" class="sipb-progress-bar">
                    <span></span>
                </div>
                <form
                    class="grid gap-4 p-5 lg:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <label class="block">
                        <span class="sipb-label">Nama barang *</span>
                        <input v-model="form.name" class="sipb-input" />
                        <p v-if="form.errors.name" class="sipb-error">
                            {{ form.errors.name }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Kategori *</span>
                        <select v-model="form.category" class="sipb-input">
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
                        <span class="sipb-label">Lokasi ditemukan *</span>
                        <select v-model="form.location" class="sipb-input">
                            <option
                                v-for="location in locations"
                                :key="location"
                                :value="location"
                            >
                                {{ location }}
                            </option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="sipb-label flex items-center gap-2">
                            <CalendarDays class="h-4 w-4 text-[#747a8b]" />
                            Tanggal/jam *
                        </span>
                        <input
                            v-model="form.found_at"
                            type="datetime-local"
                            class="sipb-input"
                        />
                        <p v-if="form.errors.found_at" class="sipb-error">
                            {{ form.errors.found_at }}
                        </p>
                    </label>
                    <div class="block">
                        <span class="sipb-label flex items-center gap-2">
                            <Image class="h-4 w-4 text-[#747a8b]" />
                            Pilih foto *
                        </span>
                        <div
                            v-if="photoPreview"
                            class="mb-3 overflow-hidden rounded-md bg-[#f8fafc]"
                        >
                            <button
                                type="button"
                                class="group block w-full"
                                title="Lihat preview foto"
                                @click="
                                    openImagePreviewSource(
                                        photoPreview,
                                        photoName || 'Preview foto barang',
                                    )
                                "
                            >
                                <img
                                    :src="photoPreview"
                                    alt="Preview foto barang"
                                    class="h-40 w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                />
                            </button>
                            <div
                                class="flex items-center justify-between gap-3 px-3 py-3"
                            >
                                <p
                                    class="min-w-0 truncate text-sm font-bold text-[#1a2134]"
                                >
                                    {{ photoName }}
                                </p>
                                <button
                                    type="button"
                                    class="sipb-icon-button shrink-0 text-[#d93c3c] hover:text-[#d93c3c]"
                                    title="Hapus foto"
                                    @click="removePhoto"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.photo" class="sipb-error">
                            {{ form.errors.photo }}
                        </p>
                        <div ref="photoDropdownButton" class="relative">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-md border border-[#e2e8f0] bg-white px-3 py-2.5 text-sm transition-colors hover:border-[#2737c9]"
                                @click="toggleDropdown"
                            >
                                <template v-if="selectedPhoto">
                                    <img
                                        :src="selectedPhoto.photo_url"
                                        class="h-10 w-10 shrink-0 rounded object-cover"
                                    />
                                    <span
                                        class="flex-1 text-left font-semibold text-[#1a2134]"
                                    >
                                        {{
                                            new Date(
                                                selectedPhoto.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "2-digit",
                                                month: "short",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })
                                        }}
                                    </span>
                                </template>
                                <template v-else>
                                    <Image
                                        class="h-5 w-5 shrink-0 text-[#747a8b]"
                                    />
                                    <span
                                        class="flex-1 text-left text-[#64748b]"
                                        >Pilih dari galeri</span
                                    >
                                </template>
                                <ChevronDown
                                    class="h-4 w-4 shrink-0 text-[#747a8b] transition-transform"
                                    :class="{ 'rotate-180': photoDropdownOpen }"
                                />
                            </button>

                            <Transition name="sipb-fade">
                                <div
                                    v-if="photoDropdownOpen"
                                    class="absolute left-0 right-0 z-10 mt-1 rounded-lg bg-white sipb-panel p-3"
                                >
                                    <div
                                        class="grid grid-cols-4 gap-2"
                                        :class="
                                            uploadedPhotos.length > 12
                                                ? 'max-h-60 overflow-y-auto'
                                                : ''
                                        "
                                    >
                                        <button
                                            v-for="photo in uploadedPhotos"
                                            :key="photo.id"
                                            type="button"
                                            :class="[
                                                'relative aspect-square overflow-hidden rounded-lg border-2 transition-all',
                                                selectedUploadedPhotoId ===
                                                photo.id
                                                    ? 'border-[#2737c9] ring-2 ring-[#2737c9]/30'
                                                    : 'border-transparent hover:border-[#cbd5e1]',
                                            ]"
                                            @click="selectUploadedPhoto(photo)"
                                        >
                                            <img
                                                :src="photo.photo_url"
                                                alt=""
                                                class="h-full w-full object-cover"
                                            />
                                            <span
                                                v-if="
                                                    selectedUploadedPhotoId ===
                                                    photo.id
                                                "
                                                class="absolute inset-0 grid place-items-center bg-[#2737c9]/20"
                                            >
                                                <CheckCircle2
                                                    class="h-5 w-5 text-white drop-shadow"
                                                />
                                            </span>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-[#cbd5e1] py-2 text-sm font-semibold text-[#64748b] transition-colors hover:border-[#2737c9] hover:text-[#2737c9]"
                                        @click="openUploadPhotosPage"
                                    >
                                        <PlusCircle class="h-4 w-4" />
                                        Upload foto baru
                                    </button>
                                </div>
                            </Transition>
                        </div>
                        <p
                            v-if="uploadedPhotos.length === 0"
                            class="mt-2 text-xs font-medium text-[#64748b]"
                        >
                            Belum ada upload.
                            <button
                                type="button"
                                class="font-bold text-[#2737c9] underline hover:text-[#202da8]"
                                @click="openUploadPhotosPage"
                            >
                                Upload foto baru
                            </button>
                        </p>
                    </div>
                    <label class="block">
                        <span class="sipb-label"
                            >Nama penemu
                            <span class="sipb-optional">(opsional)</span></span
                        >
                        <input
                            v-model="form.finder_name"
                            class="sipb-input"
                            placeholder="Nama penemu"
                        />
                    </label>
                    <label class="block">
                        <span class="sipb-label">Lokasi penyimpanan</span>
                        <input
                            v-model="form.storage_location"
                            class="sipb-input"
                            placeholder="Contoh: Resepsionis, Loker 3"
                        />
                    </label>
                    <label class="block lg:col-span-2">
                        <span class="sipb-label">Deskripsi *</span>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="sipb-input"
                        ></textarea>
                        <p v-if="form.errors.description" class="sipb-error">
                            {{ form.errors.description }}
                        </p>
                    </label>
                    <label class="block lg:col-span-2">
                        <span class="sipb-label">Catatan admin</span>
                        <textarea
                            v-model="form.admin_notes"
                            rows="2"
                            class="sipb-input"
                        ></textarea>
                    </label>
                    <div class="flex justify-end lg:col-span-2">
                        <button
                            type="button"
                            class="sipb-button-secondary mr-2"
                            @click="closeCreateForm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="sipb-button-primary inline-flex items-center gap-2 disabled:opacity-60"
                        >
                            <Save class="h-4 w-4" />
                            Simpan barang
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <section class="overflow-hidden">
            <div class="grid gap-3 p-4 md:hidden">
                <template v-if="pageLoading">
                    <article
                        v-for="index in skeletonRows"
                        :key="`item-card-skeleton-${index}`"
                        class="rounded-md sipb-panel p-4"
                    >
                        <span class="sipb-skeleton mb-3 h-3 w-36"></span>
                        <div class="flex gap-3">
                            <span
                                class="sipb-skeleton h-20 w-24 shrink-0"
                            ></span>
                            <div class="min-w-0 flex-1 space-y-2">
                                <span class="sipb-skeleton h-4 w-4/5"></span>
                                <span class="sipb-skeleton h-3 w-2/3"></span>
                                <span class="sipb-skeleton h-6 w-20"></span>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <span class="sipb-skeleton h-3 w-full"></span>
                            <span class="sipb-skeleton h-3 w-full"></span>
                        </div>
                    </article>
                </template>
                <div
                    v-else-if="itemRows.length === 0"
                    class="rounded-md bg-[#f6f7fa] p-5 text-center text-sm font-medium text-[#747a8b]"
                >
                    Tidak ada data sesuai filter.
                </div>
                <template v-else>
                    <article
                        v-for="item in itemRows"
                        :key="`card-${item.id}`"
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
                                        statusClass(item.status),
                                    ]"
                                >
                                    {{ statusLabel(item.status) }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="mt-4 grid grid-cols-2 gap-2 text-xs font-medium text-[#747a8b]"
                        >
                            <p>
                                <span class="font-bold text-[#1a2134]"
                                    >Tanggal:</span
                                >
                                {{ formatDate(item.found_at) }}
                            </p>
                            <p>
                                <span class="font-bold text-[#1a2134]"
                                    >Penemu:</span
                                >
                                {{ item.finder_name || "-" }}
                            </p>
                        </div>
                        <div class="mt-4 flex justify-end gap-2">
                            <button
                                type="button"
                                class="sipb-button-secondary inline-flex flex-1 items-center justify-center gap-2 sm:flex-none"
                                @click="openItemDetail(item)"
                            >
                                <Eye class="h-4 w-4" />
                                Detail
                            </button>
                            <button
                                type="button"
                                class="sipb-icon-button"
                                title="Edit"
                                @click="openEditForm(item)"
                            >
                                <Edit3 class="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                class="sipb-icon-button border-[#d93c3c]/30 text-[#d93c3c] hover:bg-[#d93c3c]/10"
                                title="Hapus"
                                @click="deleteItem(item)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                            <button
                                v-if="item.status !== 'tersedia'"
                                type="button"
                                class="sipb-icon-button border-[#00bf8e]/30 text-[#00bf8e] hover:bg-[#00bf8e]/10"
                                title="Publish"
                                @click="openStatusModal(item, 'tersedia')"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                            </button>
                            <button
                                v-if="item.status !== 'sudah_diambil'"
                                type="button"
                                class="sipb-icon-button"
                                title="Tandai sudah diambil"
                                @click="openStatusModal(item, 'sudah_diambil')"
                            >
                                <Save class="h-4 w-4" />
                            </button>
                        </div>
                    </article>
                </template>
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table
                    :class="[
                        'w-full divide-y divide-[#e6e9ed]',
                        isDenseTable
                            ? 'min-w-[1060px] text-xs'
                            : 'min-w-[1180px] text-sm',
                    ]"
                >
                    <colgroup>
                        <col class="w-[300px]" />
                        <col class="w-[150px]" />
                        <col class="w-[165px]" />
                        <col class="w-[170px]" />
                        <col class="w-[140px]" />
                        <col class="w-[140px]" />
                        <col class="w-[165px]" />
                        <col class="w-[210px]" />
                    </colgroup>
                    <thead class="border-b border-[#e2e8f0] text-left">
                        <tr>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Barang
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Lokasi
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Tanggal
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Penemu
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Admin
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Status
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'font-bold text-[#1a2134]',
                                ]"
                            >
                                Publikasi
                            </th>
                            <th
                                :class="[
                                    isDenseTable ? 'px-3 py-2.5' : 'px-4 py-3',
                                    'text-right font-bold text-[#1a2134]',
                                ]"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-if="pageLoading">
                            <tr
                                v-for="index in skeletonRows"
                                :key="`row-skeleton-${index}`"
                                class="align-middle"
                            >
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            :class="[
                                                'sipb-skeleton shrink-0',
                                                isDenseTable
                                                    ? 'h-9 w-11'
                                                    : 'h-12 w-14',
                                            ]"
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
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <span class="sipb-skeleton h-4 w-24"></span>
                                </td>
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <span class="sipb-skeleton h-4 w-28"></span>
                                </td>
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <span class="sipb-skeleton h-4 w-28"></span>
                                    <span
                                        class="sipb-skeleton mt-2 h-3 w-20"
                                    ></span>
                                </td>
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <span class="sipb-skeleton h-4 w-24"></span>
                                </td>
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <span class="sipb-skeleton h-7 w-24"></span>
                                </td>
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <span class="sipb-skeleton h-4 w-28"></span>
                                </td>
                                <td
                                    :class="[
                                        isDenseTable
                                            ? 'px-3 py-2'
                                            : 'px-4 py-3',
                                    ]"
                                >
                                    <div class="flex justify-end gap-1.5">
                                        <span
                                            class="sipb-skeleton h-8 w-8"
                                        ></span>
                                        <span
                                            class="sipb-skeleton h-8 w-8"
                                        ></span>
                                        <span
                                            class="sipb-skeleton h-8 w-8"
                                        ></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr
                            v-else-if="itemRows.length === 0"
                            class="border-b border-[#f1f5f9] bg-white"
                        >
                            <td
                                colspan="8"
                                :class="[
                                    isDenseTable ? 'px-3 py-8' : 'px-4 py-8',
                                    'text-center text-[#747a8b]',
                                ]"
                            >
                                Belum ada laporan.
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="item in itemRows"
                            :key="item.id"
                            class="border-b border-[#f1f5f9] align-middle odd:bg-white even:bg-[#f8f9fd] hover:bg-[#f1f5f9]"
                        >
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle',
                                ]"
                            >
                                <div
                                    :class="[
                                        'flex min-w-0 items-center',
                                        isDenseTable ? 'gap-2' : 'gap-3',
                                    ]"
                                >
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
                                <p class="truncate font-semibold">
                                    {{ item.location }}
                                </p>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle text-[#747a8b]',
                                ]"
                            >
                                <p class="leading-5">
                                    {{ formatDate(item.found_at) }}
                                </p>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle text-[#747a8b]',
                                ]"
                            >
                                <p class="truncate font-semibold">
                                    {{ item.finder_name || "-" }}
                                </p>
                                <p class="text-xs text-[#9da3b1]">
                                    {{ maskNim(item.finder_nim) }}
                                </p>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle text-[#747a8b]',
                                ]"
                            >
                                <p class="truncate font-semibold">
                                    {{ item.manager?.name ?? "-" }}
                                </p>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle',
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-flex max-w-full whitespace-nowrap rounded-md border font-medium',
                                        isDenseTable
                                            ? 'px-2 py-0.5 text-[11px]'
                                            : 'px-2.5 py-1 text-xs',
                                        statusClass(item.status),
                                    ]"
                                >
                                    {{ statusLabel(item.status) }}
                                </span>
                                <p
                                    v-if="item.is_expired"
                                    class="mt-2 text-xs font-semibold text-[#d93c3c]"
                                >
                                    Lewat 30 hari
                                </p>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle text-[#747a8b]',
                                ]"
                            >
                                <p class="leading-5">
                                    {{ formatDate(item.published_at) }}
                                </p>
                                <p
                                    v-if="item.claimed_at"
                                    class="text-xs text-[#9da3b1]"
                                >
                                    Diambil: {{ formatDate(item.claimed_at) }}
                                </p>
                            </td>
                            <td
                                :class="[
                                    isDenseTable ? 'px-3 py-2' : 'px-4 py-3',
                                    'align-middle',
                                ]"
                            >
                                <div
                                    class="flex flex-nowrap justify-end gap-1.5"
                                >
                                    <button
                                        type="button"
                                        :class="[
                                            'sipb-icon-button',
                                            isVeryDenseTable
                                                ? '!h-7 !w-7'
                                                : '!h-8 !w-8',
                                        ]"
                                        title="Lihat detail"
                                        @click="openItemDetail(item)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        :class="[
                                            'sipb-icon-button',
                                            isVeryDenseTable
                                                ? '!h-7 !w-7'
                                                : '!h-8 !w-8',
                                        ]"
                                        title="Edit data"
                                        @click="openEditForm(item)"
                                    >
                                        <Edit3 class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        :class="[
                                            'sipb-icon-button border-[#d93c3c]/30 text-[#d93c3c] hover:bg-[#d93c3c]/10',
                                            isVeryDenseTable
                                                ? '!h-7 !w-7'
                                                : '!h-8 !w-8',
                                        ]"
                                        title="Hapus data"
                                        @click="deleteItem(item)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="item.status !== 'tersedia'"
                                        type="button"
                                        :class="[
                                            'sipb-icon-button border-[#00bf8e]/30 text-[#00bf8e] hover:bg-[#00bf8e]/10',
                                            isVeryDenseTable
                                                ? '!h-7 !w-7'
                                                : '!h-8 !w-8',
                                        ]"
                                        title="Publish"
                                        @click="
                                            openStatusModal(item, 'tersedia')
                                        "
                                    >
                                        <CheckCircle2 class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="item.status !== 'sudah_diambil'"
                                        type="button"
                                        :class="[
                                            'sipb-icon-button',
                                            isVeryDenseTable
                                                ? '!h-7 !w-7'
                                                : '!h-8 !w-8',
                                        ]"
                                        title="Tandai sudah diambil"
                                        @click="
                                            openStatusModal(
                                                item,
                                                'sudah_diambil',
                                            )
                                        "
                                    >
                                        <Save class="h-4 w-4" />
                                    </button>
                                </div>
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

        <div
            v-if="selectedItem"
            class="fixed inset-0 z-[60] bg-[#1a2134]/40 backdrop-blur-[1px]"
            @click.self="closeItemDetail"
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
                            Periksa informasi sebelum publish atau tandai
                            selesai.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sipb-icon-button"
                        title="Tutup"
                        @click="closeItemDetail"
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
                                        @click="openImagePreview(selectedItem)"
                                    >
                                        <div
                                            class="absolute inset-0 bg-black/5 opacity-0 transition-opacity group-hover:opacity-100 z-10"
                                        ></div>
                                        <img
                                            :src="selectedItem.photo_url"
                                            :alt="selectedItem.name"
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
                                            ) in timelineSteps(selectedItem)"
                                            :key="step.title"
                                            class="relative flex gap-3"
                                        >
                                            <!-- Vertical Line -->
                                            <div
                                                v-if="
                                                    index !==
                                                    timelineSteps(selectedItem)
                                                        .length -
                                                        1
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
                                                statusClass(
                                                    selectedItem.status,
                                                ),
                                            ]"
                                        >
                                            <Info class="h-3.5 w-3.5" />
                                            {{
                                                statusLabel(selectedItem.status)
                                            }}
                                        </span>
                                        <span
                                            v-if="selectedItem.is_expired"
                                            class="inline-flex items-center gap-1 rounded-md bg-[#d93c3c] px-2.5 py-1 text-xs font-bold text-white shadow-sm"
                                        >
                                            <AlertTriangle
                                                class="h-3.5 w-3.5"
                                            />
                                            Lewat 30 hari
                                        </span>
                                    </div>
                                    <h3
                                        class="text-2xl font-extrabold text-gray-900 sm:text-3xl"
                                    >
                                        {{ selectedItem.name }}
                                    </h3>
                                    <div
                                        class="mt-2 flex flex-wrap items-center gap-3 text-sm font-medium text-gray-500"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1"
                                        >
                                            <Archive class="h-3.5 w-3.5" />
                                            {{ selectedItem.category }}
                                        </span>
                                        <span
                                            class="flex items-center gap-1.5 text-gray-600"
                                        >
                                            <MapPin
                                                class="h-4 w-4 text-gray-400"
                                            />
                                            Ditemukan di
                                            {{ selectedItem.location }}
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
                                            {{
                                                formatDate(
                                                    selectedItem.found_at,
                                                )
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        class="flex flex-col justify-center rounded-xl border border-gray-100 bg-white p-5 shadow-sm"
                                    >
                                        <div
                                            class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-gray-500"
                                        >
                                            <User
                                                class="h-4 w-4 text-[#2737c9]"
                                            />
                                            Data Pelapor
                                        </div>
                                        <div
                                            class="mt-2 text-sm font-semibold text-gray-900"
                                        >
                                            {{
                                                selectedItem.finder_name ||
                                                "Hamba Allah"
                                            }}
                                            <span
                                                v-if="selectedItem.finder_nim"
                                                class="ml-1 text-gray-400 font-normal"
                                                >({{
                                                    selectedItem.finder_nim
                                                }})</span
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
                                                selectedItem.storage_location ||
                                                "Belum ditentukan"
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="selectedItem.claimant_name"
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
                                            {{ selectedItem.claimant_name }}
                                            <span
                                                v-if="selectedItem.claimant_nim"
                                                class="ml-1 text-green-700 font-normal"
                                                >({{
                                                    selectedItem.claimant_nim
                                                }})</span
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
                                                selectedItem.published_at
                                                    ? formatDate(
                                                          selectedItem.published_at,
                                                      )
                                                    : "Belum dipublikasikan"
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Long Text Sections -->
                                <div
                                    class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm space-y-5"
                                >
                                    <!-- Description -->
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
                                            {{ selectedItem.description }}
                                        </p>
                                    </div>

                                    <!-- Admin Notes -->
                                    <div
                                        v-if="selectedItem.admin_notes"
                                        class="pt-2"
                                    >
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
                                            {{ selectedItem.admin_notes }}
                                        </p>
                                    </div>

                                    <!-- Validation Notes -->
                                    <div
                                        v-if="selectedItem.validation_notes"
                                        class="pt-2"
                                    >
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
                                            {{ selectedItem.validation_notes }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Duplicate Warning -->
                                <div
                                    v-if="
                                        selectedItem.duplicate_candidates
                                            ?.length
                                    "
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
                                            v-for="candidate in selectedItem.duplicate_candidates"
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
                                                        statusLabel(
                                                            candidate.status,
                                                        )
                                                    }}</span
                                                >
                                                <span>•</span>
                                                <span>{{
                                                    candidate.location
                                                }}</span>
                                                <span>•</span>
                                                <span>{{
                                                    formatDate(
                                                        candidate.created_at,
                                                    )
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
                        @click="closeItemDetail"
                    >
                        Tutup
                    </button>
                    <button
                        type="button"
                        class="sipb-button-secondary inline-flex items-center justify-center gap-2 border-[#d93c3c] text-[#d93c3c] hover:bg-[#d93c3c] hover:text-white"
                        @click="
                            deleteItem(selectedItem);
                            closeItemDetail();
                        "
                    >
                        <Trash2 class="h-4 w-4" />
                        Hapus
                    </button>
                    <button
                        type="button"
                        class="sipb-button-secondary inline-flex items-center justify-center gap-2"
                        @click="openEditForm(selectedItem)"
                    >
                        <Edit3 class="h-4 w-4" />
                        Edit
                    </button>
                    <button
                        v-if="selectedItem.status !== 'tersedia'"
                        type="button"
                        class="sipb-button-primary inline-flex items-center justify-center gap-2 bg-[#00a676] hover:bg-[#008a67]"
                        @click="openStatusModal(selectedItem, 'tersedia')"
                    >
                        <CheckCircle2 class="h-4 w-4" />
                        Publish
                    </button>
                    <button
                        v-if="selectedItem.status !== 'sudah_diambil'"
                        type="button"
                        class="sipb-button-primary inline-flex items-center justify-center gap-2"
                        @click="openStatusModal(selectedItem, 'sudah_diambil')"
                    >
                        <Save class="h-4 w-4" />
                        Tandai selesai
                    </button>
                    <a
                        v-if="selectedItem.status === 'sudah_diambil'"
                        :href="`/admin/barang/${selectedItem.id}/tanda-terima`"
                        target="_blank"
                        class="sipb-button-secondary inline-flex items-center justify-center gap-2"
                    >
                        <Download class="h-4 w-4" />
                        Tanda terima
                    </a>
                </div>
            </section>
        </div>

        <div
            v-if="editingItem"
            class="sipb-modal-backdrop"
            @click.self="closeEditForm"
        >
            <section class="sipb-modal-card">
                <div
                    class="flex items-start justify-between gap-4 border-b border-[#e6e9ed] px-5 py-4"
                >
                    <div>
                        <h2 class="sipb-section-title flex items-center gap-2">
                            <Edit3 class="h-4 w-4 text-[#2737c9]" />
                            Edit data barang
                        </h2>
                        <p class="sipb-muted mt-1 text-sm">
                            Koreksi detail laporan sebelum publish atau serah
                            barang.
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
                <div v-if="editForm.processing" class="sipb-progress-bar">
                    <span></span>
                </div>
                <form
                    class="grid gap-4 p-5 lg:grid-cols-2"
                    @submit.prevent="submitEdit"
                >
                    <label class="block">
                        <span class="sipb-label">Nama barang *</span>
                        <input v-model="editForm.name" class="sipb-input" />
                        <p v-if="editForm.errors.name" class="sipb-error">
                            {{ editForm.errors.name }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Kategori *</span>
                        <select v-model="editForm.category" class="sipb-input">
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
                        <span class="sipb-label">Lokasi *</span>
                        <select v-model="editForm.location" class="sipb-input">
                            <option
                                v-for="location in locations"
                                :key="location"
                                :value="location"
                            >
                                {{ location }}
                            </option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="sipb-label flex items-center gap-2">
                            <CalendarDays class="h-4 w-4 text-[#747a8b]" />
                            Tanggal/jam *
                        </span>
                        <input
                            v-model="editForm.found_at"
                            type="datetime-local"
                            class="sipb-input"
                        />
                        <p v-if="editForm.errors.found_at" class="sipb-error">
                            {{ editForm.errors.found_at }}
                        </p>
                    </label>
                    <label class="block">
                        <span class="sipb-label">Nama pelapor</span>
                        <input
                            v-model="editForm.finder_name"
                            class="sipb-input"
                            placeholder="Nama pelapor"
                        />
                    </label>
                    <div class="block">
                        <span class="sipb-label flex items-center gap-2">
                            <Image class="h-4 w-4 text-[#747a8b]" />
                            Ganti foto
                        </span>
                        <div
                            v-if="editPhotoPreview"
                            class="mb-3 overflow-hidden rounded-md bg-[#f8fafc]"
                        >
                            <button
                                type="button"
                                class="group block w-full"
                                title="Lihat preview foto"
                                @click="
                                    openImagePreviewSource(
                                        editPhotoPreview,
                                        editPhotoName || 'Preview foto baru',
                                    )
                                "
                            >
                                <img
                                    :src="editPhotoPreview"
                                    alt="Preview foto baru"
                                    class="h-40 w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                />
                            </button>
                            <div
                                class="flex items-center justify-between gap-3 px-3 py-3"
                            >
                                <p
                                    class="min-w-0 truncate text-sm font-bold text-[#1a2134]"
                                >
                                    {{ editPhotoName }}
                                </p>
                                <button
                                    type="button"
                                    class="sipb-icon-button shrink-0 text-[#d93c3c] hover:text-[#d93c3c]"
                                    title="Hapus foto"
                                    @click="removeEditPhoto"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                        <p v-if="editForm.errors.photo" class="sipb-error">
                            {{ editForm.errors.photo }}
                        </p>
                        <div ref="editPhotoDropdownButton" class="relative">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 rounded-md border border-[#e2e8f0] bg-white px-3 py-2.5 text-sm transition-colors hover:border-[#2737c9]"
                                @click="toggleEditDropdown"
                            >
                                <template v-if="editSelectedPhoto">
                                    <img
                                        :src="editSelectedPhoto.photo_url"
                                        class="h-10 w-10 shrink-0 rounded object-cover"
                                    />
                                    <span
                                        class="flex-1 text-left font-semibold text-[#1a2134]"
                                    >
                                        {{
                                            new Date(
                                                editSelectedPhoto.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "2-digit",
                                                month: "short",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })
                                        }}
                                    </span>
                                </template>
                                <template v-else>
                                    <Image
                                        class="h-5 w-5 shrink-0 text-[#747a8b]"
                                    />
                                    <span
                                        class="flex-1 text-left text-[#64748b]"
                                        >Pilih dari galeri</span
                                    >
                                </template>
                                <ChevronDown
                                    class="h-4 w-4 shrink-0 text-[#747a8b] transition-transform"
                                    :class="{
                                        'rotate-180': editPhotoDropdownOpen,
                                    }"
                                />
                            </button>

                            <Transition name="sipb-fade">
                                <div
                                    v-if="editPhotoDropdownOpen"
                                    class="absolute left-0 right-0 z-10 mt-1 rounded-lg bg-white sipb-panel p-3"
                                >
                                    <div
                                        class="grid grid-cols-4 gap-2"
                                        :class="
                                            uploadedPhotos.length > 12
                                                ? 'max-h-60 overflow-y-auto'
                                                : ''
                                        "
                                    >
                                        <button
                                            v-for="photo in uploadedPhotos"
                                            :key="photo.id"
                                            type="button"
                                            :class="[
                                                'relative aspect-square overflow-hidden rounded-lg border-2 transition-all',
                                                editUploadedPhotoId === photo.id
                                                    ? 'border-[#2737c9] ring-2 ring-[#2737c9]/30'
                                                    : 'border-transparent hover:border-[#cbd5e1]',
                                            ]"
                                            @click="
                                                selectEditUploadedPhoto(photo)
                                            "
                                        >
                                            <img
                                                :src="photo.photo_url"
                                                alt=""
                                                class="h-full w-full object-cover"
                                            />
                                            <span
                                                v-if="
                                                    editUploadedPhotoId ===
                                                    photo.id
                                                "
                                                class="absolute inset-0 grid place-items-center bg-[#2737c9]/20"
                                            >
                                                <CheckCircle2
                                                    class="h-5 w-5 text-white drop-shadow"
                                                />
                                            </span>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border-2 border-dashed border-[#cbd5e1] py-2 text-sm font-semibold text-[#64748b] transition-colors hover:border-[#2737c9] hover:text-[#2737c9]"
                                        @click="openUploadPhotosPage"
                                    >
                                        <PlusCircle class="h-4 w-4" />
                                        Upload foto baru
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                    <label class="block">
                        <span class="sipb-label">Lokasi penyimpanan</span>
                        <input
                            v-model="editForm.storage_location"
                            class="sipb-input"
                            placeholder="Contoh: Resepsionis, Loker 3"
                        />
                    </label>
                    <label class="block lg:col-span-2">
                        <span class="sipb-label">Deskripsi *</span>
                        <textarea
                            v-model="editForm.description"
                            rows="3"
                            class="sipb-input"
                        ></textarea>
                        <p
                            v-if="editForm.errors.description"
                            class="sipb-error"
                        >
                            {{ editForm.errors.description }}
                        </p>
                    </label>
                    <label class="block lg:col-span-2">
                        <span class="sipb-label">Catatan admin</span>
                        <textarea
                            v-model="editForm.admin_notes"
                            rows="2"
                            class="sipb-input"
                        ></textarea>
                    </label>
                    <div class="flex justify-end lg:col-span-2">
                        <button
                            type="button"
                            class="sipb-button-secondary mr-2"
                            @click="closeEditForm"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="sipb-button-primary inline-flex items-center gap-2 disabled:opacity-60"
                        >
                            <Save class="h-4 w-4" />
                            Simpan perubahan
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <div
            v-if="statusItem"
            class="sipb-modal-backdrop"
            @click.self="closeStatusModal"
        >
            <section class="sipb-modal-card max-w-[560px]">
                <div
                    class="flex items-start justify-between gap-4 border-b border-[#e6e9ed] px-5 py-4"
                >
                    <div>
                        <h2 class="sipb-section-title flex items-center gap-2">
                            <component
                                :is="statusCopy.icon"
                                class="h-4 w-4 text-[#2737c9]"
                            />
                            {{ statusCopy.title }}
                        </h2>
                        <p class="sipb-muted mt-1 text-sm">
                            {{ statusCopy.description }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="sipb-icon-button"
                        title="Tutup"
                        @click="closeStatusModal"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <form class="space-y-4 p-5" @submit.prevent="submitStatus">
                    <div class="rounded-md bg-[#f6f7fa] p-4">
                        <p class="font-extrabold text-[#1a2134]">
                            {{ statusItem.name }}
                        </p>
                        <p class="mt-1 text-sm font-semibold text-[#747a8b]">
                            {{ statusItem.category }} -
                            {{ statusItem.location }}
                        </p>
                    </div>

                    <div
                        v-if="statusAction === 'sudah_diambil'"
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <label class="block">
                            <span class="sipb-label">Nama pengambil *</span>
                            <input
                                v-model="statusForm.claimant_name"
                                class="sipb-input"
                                placeholder="Nama pemilik/pengambil"
                            />
                            <p
                                v-if="statusForm.errors.claimant_name"
                                class="sipb-error"
                            >
                                {{ statusForm.errors.claimant_name }}
                            </p>
                        </label>
                        <label class="block">
                            <span class="sipb-label">NIM pengambil</span>
                            <input
                                v-model="statusForm.claimant_nim"
                                class="sipb-input"
                                placeholder="NIM jika ada"
                            />
                            <p
                                v-if="statusForm.errors.claimant_nim"
                                class="sipb-error"
                            >
                                {{ statusForm.errors.claimant_nim }}
                            </p>
                        </label>
                    </div>

                    <div
                        v-if="statusAction === 'sudah_diambil'"
                        class="rounded-md bg-[#f6f7fa] p-4"
                    >
                        <p
                            class="mb-3 text-xs font-extrabold uppercase text-[#747a8b]"
                        >
                            Checklist validasi pengambilan
                        </p>
                        <label
                            class="mb-2 flex items-start gap-3 text-sm font-semibold text-[#1a2134]"
                        >
                            <input
                                v-model="
                                    statusForm.pickup_checklist.identity_checked
                                "
                                type="checkbox"
                                class="mt-1"
                            />
                            Identitas pengambil sudah dicocokkan.
                        </label>
                        <label
                            class="mb-2 flex items-start gap-3 text-sm font-semibold text-[#1a2134]"
                        >
                            <input
                                v-model="
                                    statusForm.pickup_checklist
                                        .ownership_checked
                                "
                                type="checkbox"
                                class="mt-1"
                            />
                            Bukti/ciri kepemilikan sudah sesuai.
                        </label>
                        <label
                            class="flex items-start gap-3 text-sm font-semibold text-[#1a2134]"
                        >
                            <input
                                v-model="
                                    statusForm.pickup_checklist
                                        .condition_checked
                                "
                                type="checkbox"
                                class="mt-1"
                            />
                            Kondisi barang sudah dikonfirmasi saat diserahkan.
                        </label>
                    </div>

                    <label class="block">
                        <span class="sipb-label"> Catatan validasi </span>
                        <textarea
                            v-model="statusForm.validation_notes"
                            rows="4"
                            class="sipb-input"
                            placeholder="Opsional, isi alasan atau bukti validasi."
                        ></textarea>
                        <p
                            v-if="statusForm.errors.validation_notes"
                            class="sipb-error"
                        >
                            {{ statusForm.errors.validation_notes }}
                        </p>
                    </label>

                    <div
                        class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <button
                            type="button"
                            class="sipb-button-secondary"
                            @click="closeStatusModal"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="statusForm.processing"
                            class="sipb-button-primary inline-flex items-center justify-center gap-2 disabled:opacity-60"
                        >
                            <component :is="statusCopy.icon" class="h-4 w-4" />
                            {{ statusCopy.button }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
        <ImagePreviewModal
            v-if="previewImage"
            :src="previewImage.src"
            :alt="previewImage.alt"
            :title="previewImage.title"
            @close="closeImagePreview"
        />
    </AppLayout>
</template>
