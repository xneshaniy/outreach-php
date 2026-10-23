<script setup>
import { ref } from 'vue'

const props = defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: { type: String, default: 'primary' },
    trend: String,
    trendUp: { type: Boolean, default: true },
    isCurrency: { type: Boolean, default: false },
})

const colorClasses = {
    primary: 'bg-primary-100 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400',
    green: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    blue: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    teal: 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    yellow: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    purple: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
}

const icons = {
    users: h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' })
    ]),
    'user-plus': h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' })
    ]),
    send: h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8' })
    ]),
    'dollar-sign': h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]),
}
</script>

<template>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ title }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                        <span v-if="isCurrency">$</span>{{ typeof value === 'number' ? value.toLocaleString() : value }}
                    </p>
                    <div v-if="trend" class="flex items-center space-x-1 mt-2">
                        <svg :class="trendUp ? 'text-green-500' : 'text-red-500'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path :stroke-linecap="trendUp ? 'round' : 'round'" :stroke-linejoin="trendUp ? 'round' : 'round'" stroke-width="2" :d="trendUp ? 'M5 10l7-7m0 0l7 7m-7-7v18' : 'M19 14l-7 7m0 0l-7-7m7 7v-18'" />
                        </svg>
                        <span :class="trendUp ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">{{ trend }}</span>
                    </div>
                </div>
                <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', colorClasses[color] || colorClasses.primary]">
                    <component :is="icons[icon] || icons.users" />
                </div>
            </div>
        </div>
    </div>
</template>