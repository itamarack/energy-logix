<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const navLinks = [
    { label: 'Formula Versions', href: '/formula-versions' },
    { label: 'Contracts', href: '/contracts' },
    { label: 'Calculations', href: '/calculations' },
]

function isActive(href: string): boolean {
    return page.url.startsWith(href)
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-50">
        <!-- Top navigation -->
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 shadow-sm"
                        >
                            <svg
                                class="h-5 w-5 text-white"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold leading-none text-slate-900">EnergyLogix</p>
                            <p class="mt-0.5 text-xs leading-none text-slate-400">Commission Engine</p>
                        </div>
                    </div>

                    <!-- Nav links -->
                    <nav class="hidden items-center gap-1 md:flex">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            :class="[
                                'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                                isActive(link.href)
                                    ? 'bg-blue-50 text-blue-700'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                            ]"
                        >
                            {{ link.label }}
                        </Link>
                    </nav>

                    <!-- Status indicator -->
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            System Online
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-4">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-center text-xs text-slate-400">
                    © 2025 EnergyLogix · Dynamic Commission Engine · All rights reserved
                </p>
            </div>
        </footer>
    </div>
</template>
