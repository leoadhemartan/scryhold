<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const sidebarOpen = ref(false);

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

const publicPages = [
    { name: 'Home', href: '/' },
    { name: 'Set Library', href: '/set-library' },
    { name: 'Card Search', href: '/cards/search' },
    { name: 'About', href: '/about' },
];

const adminPages = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Manage Cards', href: '/admin/cards' },
    { name: 'Manage Locations', href: '/admin/locations' },
];
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-900 text-gray-100">
            <!-- Hamburger Menu Button (Mobile Only) -->
            <div class="lg:hidden fixed top-4 left-4 z-50">
                <button
                    @click="toggleSidebar"
                    class="p-2 bg-gray-800 rounded-lg text-gray-100 hover:bg-gray-700 transition"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            v-if="!sidebarOpen"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            v-else
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Sidebar Overlay (Mobile) -->
            <div
                v-if="sidebarOpen"
                @click="toggleSidebar"
                class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40"
            ></div>

            <!-- Sidebar -->
            <aside
                :class="[
                    'fixed top-0 left-0 h-full bg-gray-800 border-r border-gray-700 z-40 transition-transform duration-300',
                    'w-64',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                ]"
            >
                <div class="flex flex-col h-full">
                    <!-- Logo -->
                    <div class="p-6 border-b border-gray-700">
                        <Link href="/" @click="sidebarOpen = false">
                            <ApplicationLogo class="h-16 w-16 fill-current text-gray-500" />
                        </Link>
                    </div>

                    <!-- Navigation -->
                    <nav class="flex-1 overflow-y-auto p-4">
                        <!-- Public Pages -->
                        <div class="mb-6">
                            <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                Public
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="page in publicPages" :key="page.href">
                                    <Link
                                        :href="page.href"
                                        @click="sidebarOpen = false"
                                        class="block px-4 py-2 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition"
                                        :class="{ 'bg-purple-600 text-white': $page.url === page.href }"
                                    >
                                        {{ page.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Admin Pages -->
                        <div>
                            <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                Admin
                            </h3>
                            <ul class="space-y-2">
                                <li v-for="page in adminPages" :key="page.href">
                                    <Link
                                        :href="page.href"
                                        @click="sidebarOpen = false"
                                        class="block px-4 py-2 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition"
                                        :class="{ 'bg-purple-600 text-white': $page.url.startsWith(page.href) }"
                                    >
                                        {{ page.name }}
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </nav>

                    <!-- Footer -->
                    <div class="p-4 border-t border-gray-700">
                        <div class="mb-2 px-4 text-sm text-gray-400">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            @click="sidebarOpen = false"
                            class="block w-full px-4 py-2 text-center rounded-lg bg-red-600 hover:bg-red-700 text-white transition"
                        >
                            Logout
                        </Link>
                    </div>
                </div>
            </aside>

            <!-- Main Content with top nav bar -->
            <div class="lg:ml-64">
                <!-- Page Content -->
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
