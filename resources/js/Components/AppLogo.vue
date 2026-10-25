<template>
    <div :class="['inline-flex items-center', containerClass]">
        <div
            :class="[
                'relative flex items-center justify-center overflow-hidden transition-all duration-200',
                badgeClass,
                sizeClasses.box
            ]"
        >
            <img
                :src="logoSrc"
                :alt="alt || 'Outreach.click'"
                :class="[
                    'object-contain select-none transition-transform duration-200',
                    sizeClasses.img
                ]"
                loading="eager"
            />
        </div>

        <div v-if="showText" :class="['flex flex-col', textSpacing]">
            <span :class="['font-bold tracking-tight leading-tight', textClasses]">
                Outreach<span :class="accentClass">.click</span>
            </span>
            <span v-if="subText" :class="['block uppercase tracking-wider font-semibold', subTextClasses]">
                {{ subText }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    size: {
        type: String,
        default: 'md', // 'xs', 'sm', 'md', 'lg', 'xl'
    },
    variant: {
        type: String,
        default: 'auto', // 'auto', 'white-badge', 'transparent', 'raw'
    },
    showText: {
        type: Boolean,
        default: false,
    },
    subText: {
        type: String,
        default: '',
    },
    theme: {
        type: String,
        default: 'dark', // 'dark', 'light'
    },
    alt: {
        type: String,
        default: 'Outreach.click Logo',
    },
    containerClass: {
        type: String,
        default: '',
    },
})

const logoSrc = computed(() => {
    if (props.variant === 'transparent') {
        return '/images/logo-transparent.png'
    }
    return '/images/logo.png'
})

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return { box: 'w-6 h-6', img: 'max-h-5 max-w-5' }
        case 'sm':
            return { box: 'w-8 h-8', img: 'max-h-7 max-w-7' }
        case 'md':
            return { box: 'w-10 h-10', img: 'max-h-9 max-w-9' }
        case 'lg':
            return { box: 'w-16 h-16', img: 'max-h-14 max-w-14' }
        case 'xl':
            return { box: 'w-24 h-24', img: 'max-h-20 max-w-20' }
        case 'auth':
            return { box: 'w-24 h-24 sm:w-28 sm:h-28 mx-auto', img: 'max-h-full max-w-full' }
        default:
            return { box: 'w-10 h-10', img: 'max-h-9 max-w-9' }
    }
})

const badgeClass = computed(() => {
    if (props.variant === 'raw') {
        return 'bg-transparent'
    }
    if (props.variant === 'white-badge' || props.theme === 'dark') {
        return 'bg-white rounded-xl shadow-sm p-1 ring-1 ring-white/10'
    }
    return 'bg-white rounded-xl shadow-sm p-1'
})

const textSpacing = computed(() => {
    return props.size === 'sm' || props.size === 'xs' ? 'ml-2' : 'ml-3'
})

const textClasses = computed(() => {
    if (props.theme === 'dark') {
        return 'text-white text-base sm:text-lg'
    }
    return 'text-gray-900 dark:text-white text-base sm:text-lg'
})

const accentClass = computed(() => {
    return props.theme === 'dark' ? 'text-indigo-400' : 'text-primary-600 dark:text-primary-400'
})

const subTextClasses = computed(() => {
    return 'text-[10px] text-amber-400 font-semibold'
})
</script>
