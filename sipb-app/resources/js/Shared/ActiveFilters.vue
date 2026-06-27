<script setup>
import { X } from "@lucide/vue";

defineProps({
    filters: { type: Array, default: () => [] },
});

const emit = defineEmits(["remove"]);
</script>

<template>
    <div v-if="filters.length" class="mb-4 flex flex-wrap gap-2">
        <span
            v-for="f in filters"
            :key="f.key"
            class="inline-flex items-center gap-1.5 rounded-full border border-[#e2e8f0] bg-[#f8f9fd] px-3 py-1 text-xs font-semibold text-[#1a2134]"
        >
            {{ f.label }}
            <button
                type="button"
                class="text-[#94a3b8] hover:text-[#d93c3c]"
                @click="emit('remove', f.key)"
            >
                <X class="h-3 w-3" />
            </button>
        </span>
    </div>
</template>
