<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bot, ChevronDown, ChevronLeft, ChevronRight, ExternalLink, ImagePlus, Loader2, MessageCircle, MessagesSquare, Send, Sparkles, X } from '@lucide/vue';
import { computed, nextTick, ref } from 'vue';

const page = usePage();
const isOpen = ref(false);
const currentView = ref('menu');
const input = ref('');
const preChatTopic = ref('');
const preChatMessage = ref('');
const isSending = ref(false);
const error = ref('');
const listRef = ref(null);
const fileInputRef = ref(null);
const selectedImagePreview = ref('');
const messages = ref([
    {
        role: 'assistant',
        content: 'Halo, saya asisten SIPB UYM. Saya bisa bantu cari barang, atau jelaskan cara mengambil barang.',
    },
]);
const latestAction = ref(null);

const faqs = [
    'Cara mencari barang yang hilang?',
    'Syarat mengambil barang?',
    'Dimana lokasi resepsionis?',
];

const csrfToken = computed(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '');

function toggleWidget() {
    isOpen.value = !isOpen.value;
    
    if (isOpen.value && currentView.value === 'chat') {
        nextTick(scrollToBottom);
    }
}

function openPreChat() {
    currentView.value = 'pre-chat';
}

function submitPreChat() {
    input.value = `[Topik: ${preChatTopic.value}] ${preChatMessage.value.trim()}`;
    currentView.value = 'chat';
    preChatTopic.value = '';
    preChatMessage.value = '';
}

function useFAQ(text) {
    input.value = text;
    currentView.value = 'chat';
}

function onImageSelect(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.size > 4 * 1024 * 1024) {
        error.value = 'Foto maksimal 4MB.';
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        selectedImagePreview.value = reader.result;
    };
    reader.readAsDataURL(file);
    event.target.value = '';
}

function clearImage() {
    selectedImagePreview.value = '';
}

function scrollToBottom() {
    if (listRef.value) {
        listRef.value.scrollTop = listRef.value.scrollHeight;
    }
}

async function sendMessage() {
    const content = input.value.trim();

    if (!content || isSending.value) {
        return;
    }

    const imageData = selectedImagePreview.value;

    messages.value.push({
        role: 'user',
        content,
        image: imageData || null,
    });
    input.value = '';
    error.value = '';
    latestAction.value = null;
    isSending.value = true;
    selectedImagePreview.value = '';
    await nextTick(scrollToBottom);

    try {
        const response = await fetch('/ai/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken.value,
            },
            body: JSON.stringify({
                path: page.url,
                image_data: imageData || null,
                messages: messages.value
                    .filter((message) => ['user', 'assistant'].includes(message.role))
                    .slice(-10),
            }),
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.message || 'AI belum bisa menjawab saat ini.');
        }

        messages.value.push({
            role: 'assistant',
            content: data.reply || 'Saya siap membantu layanan SIPB UYM.',
            items: data.items || [],
        });
        latestAction.value = data.action || null;
    } catch (exception) {
        error.value = exception.message || 'Koneksi AI sedang bermasalah.';
        messages.value.push({
            role: 'assistant',
            content: 'Maaf, AI sedang belum stabil. Kamu tetap bisa buka Cari Barang atau Bantuan.',
            items: [],
        });
        latestAction.value = {
            type: 'help',
            label: 'Buka bantuan',
            url: '/bantuan',
        };
    } finally {
        isSending.value = false;
        await nextTick(scrollToBottom);
    }
}

function openAction(action) {
    if (!action?.url) {
        return;
    }

    isOpen.value = false;
    router.visit(action.url);
}

function onPhotoError(event) {
    event.target.src = 'data:image/svg+xml,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="160" height="80" viewBox="0 0 160 80"><rect fill="#eef0f5" width="160" height="80"/><text x="80" y="44" text-anchor="middle" fill="#9da3b1" font-size="12" font-family="sans-serif">Gambar</text></svg>');
}
function formatMessage(content) {
    if (!content) return '';
    return content
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
}
</script>

<template>
    <div class="fixed bottom-5 right-4 z-[60] sm:right-5">
        <Transition name="sipb-list">
            <section
                v-if="isOpen"
                class="mb-3 flex h-[min(620px,calc(100vh-120px))] w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl bg-[#f6f7fa] shadow-[0_24px_70px_rgba(26,33,52,0.22)] sm:w-[390px]"
            >
                <!-- MENU VIEW -->
                <div v-if="currentView === 'menu'" class="flex h-full flex-col overflow-y-auto">
                    <!-- Header -->
                    <div class="px-6 pb-6 pt-10">
                        <div class="mb-5 grid h-14 w-14 place-items-center rounded-full bg-[#2737c9] text-white">
                            <Bot class="h-8 w-8" />
                        </div>
                        <h2 class="text-2xl font-extrabold text-[#1a2134]">
                            Halo SIPB 👋
                        </h2>
                        <p class="mt-2 text-sm font-medium leading-6 text-[#747a8b]">
                            Terima kasih telah mengunjungi SIPB UYM! Ada yang bisa kami bantu untuk pencarian barangmu?
                        </p>
                    </div>

                    <!-- Content -->
                    <div class="px-5 pb-6 space-y-4">
                        <!-- Chat CTA Card -->
                        <div class="rounded-xl bg-white p-5 shadow-[0_8px_22px_rgba(220,221,234,0.3)]">
                            <p class="text-sm font-bold text-[#1a2134]">Asisten AI tersedia</p>
                            <p class="mt-1 text-xs text-[#747a8b]">Online saat ini</p>
                            <button
                                type="button"
                                class="mt-4 flex items-center gap-1 text-sm font-extrabold text-[#2737c9] hover:text-[#1e2a96]"
                                @click="openPreChat"
                            >
                                Mulai Percakapan <ChevronRight class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- FAQ Card -->
                        <div class="rounded-xl bg-white shadow-[0_8px_22px_rgba(220,221,234,0.3)]">
                            <h3 class="border-b border-[#eef0f5] px-5 py-4 text-sm font-bold text-[#1a2134]">
                                Artikel Populer
                            </h3>
                            <div class="flex flex-col">
                                <button
                                    v-for="(faq, index) in faqs"
                                    :key="index"
                                    type="button"
                                    class="flex items-center justify-between border-b border-[#eef0f5] px-5 py-3.5 text-left text-sm font-medium text-[#747a8b] transition-colors last:border-0 hover:bg-[#fafbff] hover:text-[#2737c9]"
                                    @click="useFAQ(faq)"
                                >
                                    {{ faq }}
                                    <ChevronRight class="h-4 w-4 shrink-0 text-[#cbd0dd]" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRE-CHAT VIEW -->
                <div v-else-if="currentView === 'pre-chat'" class="flex h-full flex-col bg-[#f6f7fa]">
                    <header class="flex items-center gap-3 border-b border-[#eef0f5] bg-white px-2 py-2 sm:px-3">
                        <button type="button" class="sipb-icon-button !h-9 !w-9" title="Kembali" @click="currentView = 'menu'">
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-[#2737c9] text-white">
                                <Bot class="h-5 w-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-extrabold text-[#1a2134]">Asisten SIPB UYM</span>
                                <span class="mt-0.5 block truncate text-xs font-semibold text-[#747a8b]">Online saat ini</span>
                            </span>
                        </div>
                    </header>

                    <div class="flex-1 overflow-y-auto px-5 py-6">
                        <p class="mb-6 text-sm font-medium text-[#1a2134]">Beri tahu apa yang bisa dibantu...</p>
                        
                        <form class="space-y-4" @submit.prevent="submitPreChat">
                            <div>
                                <label class="mb-1.5 block text-[13px] font-bold text-[#1a2134]">Pilih Topik</label>
                                <div class="relative">
                                    <select
                                        v-model="preChatTopic"
                                        required
                                        class="w-full appearance-none rounded-md border border-[#e6e9ed] bg-white px-3 py-2.5 text-sm font-medium text-[#1a2134] outline-none focus:border-[#2737c9] focus:ring-2 focus:ring-[#2737c9]/10"
                                    >
                                        <option value="" disabled>Pilih Topik</option>
                                        <option value="Lapor Barang Hilang">Lapor Barang Hilang</option>
                                        <option value="Tanya Prosedur">Tanya Prosedur</option>
                                        <option value="Cari Barang">Cari Barang</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                    <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#747a8b]" />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-[13px] font-bold text-[#1a2134]">Pesan</label>
                                <textarea
                                    v-model="preChatMessage"
                                    required
                                    rows="4"
                                    class="w-full resize-none rounded-md border border-[#e6e9ed] bg-white px-3 py-2.5 text-sm font-medium text-[#1a2134] outline-none placeholder:text-[#9da3b1] focus:border-[#2737c9] focus:ring-2 focus:ring-[#2737c9]/10"
                                    placeholder="Harap masukkan pesan Anda"
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="!preChatTopic || !preChatMessage.trim()"
                                class="mt-2 flex w-full items-center justify-center rounded-md bg-[#2737c9] px-4 py-2.5 text-sm font-extrabold text-white transition-colors hover:bg-[#1e2a96] disabled:opacity-50"
                            >
                                Mulai Percakapan
                            </button>
                        </form>
                    </div>
                    <div class="py-3 text-center text-xs font-semibold text-[#9da3b1]">
                        Powered by SIPB UYM
                    </div>
                </div>

                <!-- CHAT VIEW -->
                <div v-else class="flex h-full flex-col bg-white">
                    <header class="flex items-center gap-3 border-b border-[#eef0f5] px-2 py-2 sm:px-3">
                        <button type="button" class="sipb-icon-button !h-9 !w-9" title="Kembali" @click="currentView = 'menu'">
                            <ChevronLeft class="h-5 w-5" />
                        </button>
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-[#2737c9] text-white">
                                <Bot class="h-5 w-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-extrabold text-[#1a2134]">Asisten SIPB UYM</span>
                                <span class="mt-0.5 block truncate text-xs font-semibold text-[#747a8b]">Cari barang dan bantuan</span>
                            </span>
                        </div>
                        <button type="button" class="sipb-icon-button !h-9 !w-9" title="Tutup asisten" @click="isOpen = false">
                            <X class="h-5 w-5" />
                        </button>
                    </header>

                    <div ref="listRef" class="min-h-0 flex-1 space-y-3 overflow-y-auto bg-[#f7f8fc] px-4 py-4">
                        <div
                            v-for="(message, index) in messages"
                            :key="`${message.role}-${index}`"
                            :class="['flex', message.role === 'user' ? 'justify-end' : 'justify-start']"
                        >
                            <div
                                :class="[
                                    'max-w-[82%] rounded-xl px-3.5 py-2.5 text-sm font-medium leading-6',
                                    message.role === 'user'
                                        ? 'rounded-br-sm bg-[#2737c9] text-white'
                                        : 'rounded-bl-sm bg-white text-[#1a2134] shadow-[0_8px_22px_rgba(220,221,234,0.18)]',
                                ]"
                            >
                                <img
                                    v-if="message.image"
                                    :src="message.image"
                                    class="mb-1.5 h-28 w-full rounded-md object-cover"
                                />
                                <span class="whitespace-pre-wrap" v-html="formatMessage(message.content)"></span>
                                <div v-if="message.items?.length" class="mt-2 grid grid-cols-2 gap-2">
                                    <Link
                                        v-for="item in message.items"
                                        :key="item.id"
                                        :href="`/barang/${item.id}`"
                                        class="overflow-hidden rounded-md border bg-white"
                                    >
                                        <img
                                            :src="item.photo_url"
                                            :alt="item.name"
                                            class="h-20 w-full object-cover"
                                            @error="onPhotoError"
                                        />
                                        <p class="truncate px-2 py-1 text-xs font-bold">{{ item.name }}</p>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <div v-if="isSending" class="flex justify-start">
                            <div class="inline-flex items-center gap-2 rounded-xl rounded-bl-sm bg-white px-3.5 py-2.5 text-sm font-bold text-[#747a8b] shadow-[0_8px_22px_rgba(220,221,234,0.18)]">
                                <Loader2 class="h-4 w-4 animate-spin" />
                                AI sedang membaca konteks...
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#eef0f5] bg-white p-3">
                        <button
                            v-if="latestAction"
                            type="button"
                            class="mb-3 inline-flex w-full items-center justify-center gap-2 rounded-md border border-[#2737c9]/20 bg-[#edf2ff] px-3 py-2 text-sm font-extrabold text-[#2737c9] hover:bg-[#dfe7ff]"
                            @click="openAction(latestAction)"
                        >
                            <ExternalLink class="h-4 w-4" />
                            {{ latestAction.label }}
                        </button>

                        <p v-if="error" class="mb-2 text-xs font-semibold text-[#d93c3c]">
                            {{ error }}
                        </p>

                        <div v-if="selectedImagePreview" class="relative mb-2 inline-block">
                            <img :src="selectedImagePreview" class="h-14 w-14 rounded-md border object-cover" />
                            <button
                                type="button"
                                class="absolute -right-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full bg-[#d93c3c] text-xs font-bold text-white shadow"
                                title="Hapus foto"
                                @click="clearImage"
                            >×</button>
                        </div>

                        <form class="flex gap-2" @submit.prevent="sendMessage">
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept="image/*"
                                hidden
                                @change="onImageSelect"
                            />
                            <button
                                type="button"
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-md border border-[#e6e9ed] text-[#747a8b] hover:bg-[#f6f7fa]"
                                title="Upload foto"
                                @click="fileInputRef?.click()"
                            >
                                <ImagePlus class="h-4 w-4" />
                            </button>
                            <input
                                v-model="input"
                                class="min-h-10 flex-1 rounded-md border border-[#e6e9ed] px-3 text-sm font-medium text-[#1a2134] outline-none placeholder:text-[#9da3b1] focus:border-[#2737c9] focus:ring-2 focus:ring-[#2737c9]/10"
                                placeholder="Ketik pesan disini..."
                            />
                            <button
                                type="submit"
                                :disabled="isSending || !input.trim()"
                                class="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-[#2737c9] text-white disabled:opacity-50"
                                title="Kirim"
                            >
                                <Send class="h-4 w-4" />
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </Transition>

        <button
            type="button"
            class="ml-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#2737c9] text-white shadow-[0_18px_45px_rgba(26,33,52,0.24)] transition-transform hover:scale-110 active:scale-95"
            title="Buka asisten AI"
            @click="toggleWidget"
        >
            <X v-if="isOpen" class="h-6 w-6" />
            <MessagesSquare v-else class="h-6 w-6" />
        </button>
    </div>
</template>
