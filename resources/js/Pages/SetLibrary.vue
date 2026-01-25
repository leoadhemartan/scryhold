<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    sets: Object,
    filters: Object
});

const search = ref(props.filters?.search || '');
const sortBy = ref(props.filters?.sort || 'date_desc');

const formatDate = (dateString) => {
    if (!dateString) return 'Release date TBA';
    
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
};

const getImageUrl = (svgUrl) => {
    if (!svgUrl) return null;
    return `/storage/${svgUrl}`;
};

const applyFilters = () => {
    router.get('/set-library', {
        search: search.value,
        sort: sortBy.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

watch([search, sortBy], () => {
    applyFilters();
});
</script>

<template>
    <GuestLayout>
        <Head title="Set Library" />

        <main>
            <div class="max-w-7xl mx-auto p-4">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-bold text-gray-100">Set Library</h1>
                </div>
                
                <div class="bg-gray-800 p-4 rounded-lg shadow text-gray-100">
                    <!-- Search and Sort Controls -->
                    <div class="mb-4 flex flex-col sm:flex-row gap-3">
                        <!-- Search Bar -->
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search sets by name..."
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                            />
                        </div>
                        
                        <!-- Sort Selector -->
                        <div>
                            <select
                                v-model="sortBy"
                                class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"
                            >
                                <option value="name_asc">Sort by Name (A-Z)</option>
                                <option value="name_desc">Sort by Name (Z-A)</option>
                                <option value="date_asc">Sort by Date (Oldest)</option>
                                <option value="date_desc">Sort by Date (Newest)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="!sets.data || sets.data.length === 0" class="text-gray-400">
                        No sets available. Please update the set library.
                    </div>

                    <!-- Sets Grid -->
                    <div v-else>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
                            <div 
                                v-for="set in sets.data" 
                                :key="set.id"
                                class="bg-gray-700/50 backdrop-blur-sm rounded-lg p-4 hover:bg-gray-700 transition-all duration-200 hover:scale-105 border border-gray-600/50"
                                style="min-width: 120px"
                            >
                                <!-- Set Icon -->
                                <div class="flex items-center justify-center mb-3">
                                    <img
                                        v-if="getImageUrl(set.set_svg_url)"
                                        :src="getImageUrl(set.set_svg_url)"
                                        :alt="`${set.set_name} icon`"
                                        class="w-20 h-20 object-contain"
                                        @error="$event.target.style.display='none'"
                                    />
                                    <div
                                        v-else
                                        class="w-20 h-20 bg-gray-600 rounded-lg flex items-center justify-center"
                                    >
                                        <span class="text-gray-400 text-xs font-bold">{{ set.set_code }}</span>
                                    </div>
                                </div>

                                <!-- Set Name & Code -->
                                <div class="text-center">
                                    <p class="text-white font-medium text-sm mb-1">
                                        {{ set.set_name }}
                                    </p>
                                    <p class="text-purple-400 text-xs font-mono mb-2">
                                        ({{ set.set_code }})
                                    </p>
                                    
                                    <!-- Release Date -->
                                    <p 
                                        class="text-xs"
                                        :class="set.set_release_date ? 'text-gray-400' : 'text-gray-500 italic'"
                                    >
                                        {{ formatDate(set.set_release_date) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination Controls -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-700">
                            <!-- Page Info -->
                            <div class="text-gray-300 text-sm">
                                Showing <span class="font-semibold text-white">{{ sets.from || 0 }}</span> to 
                                <span class="font-semibold text-white">{{ sets.to || 0 }}</span> of 
                                <span class="font-semibold text-white">{{ sets.total }}</span> sets
                            </div>

                            <!-- Pagination Links -->
                            <div class="flex items-center space-x-2">
                                <template v-for="link in sets.links" :key="link.label">
                                    <!-- Previous Button -->
                                    <Link
                                        v-if="link.label.includes('Previous')"
                                        :href="link.url || '#'"
                                        :class="[
                                            'px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200',
                                            link.url 
                                                ? 'bg-gray-700 text-white hover:bg-gray-600' 
                                                : 'bg-gray-900 text-gray-500 cursor-not-allowed'
                                        ]"
                                        :disabled="!link.url"
                                    >
                                        Previous
                                    </Link>

                                    <!-- Page Numbers -->
                                    <Link
                                        v-else-if="!link.label.includes('Next') && !link.label.includes('Previous')"
                                        :href="link.url || '#'"
                                        :class="[
                                            'px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200',
                                            link.active 
                                                ? 'bg-purple-600 text-white' 
                                                : link.url 
                                                    ? 'bg-gray-700 text-white hover:bg-gray-600' 
                                                    : 'text-gray-500'
                                        ]"
                                        v-html="link.label"
                                    />

                                    <!-- Next Button -->
                                    <Link
                                        v-else-if="link.label.includes('Next')"
                                        :href="link.url || '#'"
                                        :class="[
                                            'px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200',
                                            link.url 
                                                ? 'bg-gray-700 text-white hover:bg-gray-600' 
                                                : 'bg-gray-900 text-gray-500 cursor-not-allowed'
                                        ]"
                                        :disabled="!link.url"
                                    >
                                        Next
                                    </Link>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </GuestLayout>
</template>
