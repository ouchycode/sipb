<script setup>
import { Search } from "@lucide/vue";

defineProps({
    modelValue: { type: String, default: "" },
    placeholder: { type: String, default: "Cari" },
});

const emit = defineEmits(["update:modelValue", "search"]);
</script>

<template>
    <div
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex flex-1 items-center gap-3">
            <Search class="h-5 w-5 text-[#2737c9]" stroke-width="2.5" />
            <div class="h-5 w-[1px] bg-[#e2e8f0]"></div>
            <input
                :value="modelValue"
                type="text"
                :placeholder="placeholder"
                class="w-full border-none bg-transparent p-0 text-sm text-[#1a2134] placeholder:text-[#94a3b8] focus:outline-none focus:ring-0 sm:max-w-md"
                @input="emit('update:modelValue', $event.target.value)"
                @keyup.enter="emit('search')"
            />
        </div>
        <div class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
