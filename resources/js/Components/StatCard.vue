<script setup>
import { h } from 'vue'

defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    color: { type: String, default: 'primary' },
    trend: String,
    trendUp: { type: Boolean, default: true },
    isCurrency: { type: Boolean, default: false },
})

const colorClasses = {
    primary: 'bg-primary/10 text-primary',
    green: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    blue: 'bg-sky-100 text-sky-600 dark:bg-sky-900/30 dark:text-sky-400',
    teal: 'bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400',
    red: 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    yellow: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
    purple: 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400',
}

const icons = {
    users: h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' })
    ]),
    'user-plus': h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z' })
    ]),
    send: h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8' })
    ]),
    'dollar-sign': h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]),
}
</script>

<template>
    <div class="card transition-all duration-200 hover:shadow-card-hover">
        <div class="card-body">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight">
                        <span v-if="isCurrency" class="text-xl">$</span>{{ typeof value === 'number' ? value.toLocaleString() : value }}
                    </p>
                    <p v-if="trend" class="mt-2 text-xs font-medium" :class="trendUp ? 'text-emerald-600' : 'text-red-600'">
                        {{ trend }}
                    </p>
                </div>
                <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-xl', colorClasses[color] || colorClasses.primary]">
                    <component :is="icons[icon] || icons.users" />
                </div>
            </div>
        </div>
    </div>
</template>
