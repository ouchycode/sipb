<script setup>
import { Link } from "@inertiajs/vue3";
import { ChevronLeft, ChevronRight } from "@lucide/vue";

defineProps({
    meta: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["update:perPage"]);

function onPerPageChange(event) {
    emit("update:perPage", event.target.value);
}
</script>

<template>
    <nav
        v-if="meta.total > 0"
        class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-[#64748b]">
            <span>Tampilkan</span>
            <select
                :value="meta.per_page"
                class="w-full rounded border border-[#e2e8f0] bg-white px-2 py-1 text-sm font-medium text-[#1a2134] focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9] sm:w-auto"
                @change="onPerPageChange"
            >
                <option value="5">5 per halaman</option>
                <option value="10">10 per halaman</option>
                <option value="20">20 per halaman</option>
                <option value="50">50 per halaman</option>
            </select>
            <span>Total {{ meta.total }} data</span>
        </div>

        <div
            v-if="meta.links && meta.last_page > 1"
            class="flex flex-wrap items-center gap-1"
        >
            <template v-for="(link, index) in meta.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    :class="[
                        'grid h-8 min-w-[32px] place-items-center rounded border px-2 text-sm font-semibold transition-colors',
                        link.active
                            ? 'border-[#2737c9] bg-[#2737c9] text-white'
                            : 'border-[#e2e8f0] bg-white text-[#64748b] hover:border-[#cbd5e1] hover:bg-[#f8fafc]',
                    ]"
                >
                    <ChevronLeft v-if="index === 0" class="h-4 w-4" />
                    <ChevronRight
                        v-else-if="index === meta.links.length - 1"
                        class="h-4 w-4"
                    />
                    <span v-else v-html="link.label"></span>
                </Link>
                <span
                    v-else
                    class="grid h-8 min-w-[32px] place-items-center rounded border border-[#e2e8f0] bg-[#f8fafc] px-2 text-sm font-semibold text-[#cbd5e1]"
                >
                    <ChevronLeft v-if="index === 0" class="h-4 w-4" />
                    <ChevronRight
                        v-else-if="index === meta.links.length - 1"
                        class="h-4 w-4"
                    />
                    <span v-else v-html="link.label"></span>
                </span>
            </template>
        </div>
    </nav>
</template>
