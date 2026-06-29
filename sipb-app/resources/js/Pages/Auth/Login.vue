<script setup>
import { Link, useForm, Head } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';

const campusLogo = "/assets/logo-uym.png";
const showPassword = ref(false);

const form = useForm({
    login: '',
    password: '',
});

function submit() {
    if (form.processing) {
        return;
    }

    form.clearErrors();

    let hasError = false;
    if (!form.login) {
        form.setError('login', 'Email/Username wajib diisi.');
        hasError = true;
    }
    if (!form.password) {
        form.setError('password', 'Password wajib diisi.');
        hasError = true;
    }
    if (hasError) return;

    form.post('/login');
}
</script>

<template>
    <Head title="Login Admin - SIPB" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-white p-4">
        <div class="w-full max-w-md text-center">
            <!-- Header Logo & Title -->
            <img :src="campusLogo" alt="Logo UYM" class="mx-auto mb-4 h-28 w-28 object-contain" />
            <p class="text-base text-gray-700">Selamat Datang di SIPB</p>
            <h1 class="mb-12 text-2xl font-bold text-gray-900">Universitas Yatsi Madani</h1>

            <!-- Form Container -->
            <div class="text-left px-2">
                <h2 class="text-2xl font-bold text-gray-900">Login Portal Admin</h2>
                <p class="mb-6 mt-1 text-sm text-gray-500">Lengkapi data berikut ini!</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-600">Email / Username</label>
                        <input
                            v-model="form.login"
                            type="text"
                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm placeholder-gray-300 focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]"
                            placeholder="Email atau Username"
                            autocomplete="username"
                        />
                        <p v-if="form.errors.login" class="mt-1 text-xs text-red-500">{{ form.errors.login }}</p>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-600">Password</label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full rounded-md border border-gray-200 py-2 pl-3 pr-10 text-sm placeholder-gray-300 focus:border-[#2737c9] focus:outline-none focus:ring-1 focus:ring-[#2737c9]"
                                placeholder="Password"
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#2737c9]"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <div class="pt-4 text-center">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded bg-[#2737c9] px-6 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-800 disabled:opacity-70"
                        >
                            {{ form.processing ? 'Memeriksa...' : 'Login SIPB' }}
                        </button>
                    </div>
                </form>
            </div>
            
            <p class="mt-16 text-xs text-gray-400">
                Copyright © 2026 SIPB UYM. All rights reserved.
            </p>
        </div>
    </div>
</template>
