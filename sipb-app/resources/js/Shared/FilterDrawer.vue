<script setup>
import { X } from "@lucide/vue";

defineProps({
    show: { type: Boolean, default: false },
});

const emit = defineEmits(["close", "apply", "reset"]);
</script>

<template>
    <div
        class="fixed inset-0 z-[60] overflow-hidden"
        :class="show ? 'pointer-events-auto' : 'pointer-events-none'"
    >
        <div
            class="absolute inset-0 bg-black/40 transition-opacity duration-300"
            :class="show ? 'opacity-100' : 'opacity-0'"
            @click="emit('close')"
        ></div>
        <div class="absolute inset-y-0 right-0 flex max-w-full">
            <div
                class="w-screen max-w-md transform transition duration-300 ease-in-out"
                :class="show ? 'translate-x-0' : 'translate-x-full'"
            >
                <div class="flex h-full flex-col bg-white shadow-xl">
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-[#e2e8f0] px-6 py-5"
                    >
                        <h2 class="text-2xl font-bold text-[#1a2134]">
                            Filter
                        </h2>
                        <button
                            type="button"
                            class="text-[#64748b] hover:text-[#1a2134]"
                            @click="emit('close')"
                        >
                            <X class="h-6 w-6" />
                        </button>
                    </div>
                    <!-- Body -->
                    <form
                        class="flex h-full flex-col"
                        @submit.prevent="
                            () => {
                                emit('apply');
                                emit('close');
                            }
                        "
                    >
                        <div class="flex-1 overflow-y-auto px-6 py-6">
                            <div class="grid gap-6">
                                <slot />
                            </div>
                        </div>
                        <!-- Footer -->
                        <div class="border-t border-[#e2e8f0] px-6 py-5">
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="w-1/2 rounded-md border border-[#2737c9] py-2.5 text-sm font-bold text-[#2737c9] transition-colors hover:bg-[#f8f9fd]"
                                    @click="
                                        () => {
                                            emit('reset');
                                            emit('close');
                                        }
                                    "
                                >
                                    Reset
                                </button>
                                <button
                                    type="submit"
                                    class="w-1/2 rounded-md bg-[#2737c9] py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#1f2dad]"
                                >
                                    Terapkan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
