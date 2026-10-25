<template>
    <div :class="['inline-flex items-center', containerClass]">
        <!-- 1. Horizontal Vector SVG variant (Direct SVG rendering for maximum sharpness) -->
        <template v-if="variant === 'horizontal'">
            <img
                :src="theme === 'dark' ? '/images/logo-light.svg' : '/images/logo-dark.svg'"
                :alt="alt || 'Outreach.click'"
                :class="['h-auto select-none object-contain', horizontalSizeClass]"
                loading="eager"
            />
        </template>

        <!-- 2. Stacked Vector SVG variant -->
        <template v-else-if="variant === 'stacked'">
            <div class="flex flex-col items-center text-center">
                <img
                    src="/images/logo-stacked.svg"
                    :alt="alt || 'Outreach.click'"
                    :class="['h-auto select-none object-contain', stackedSizeClass]"
                    loading="eager"
                />
            </div>
        </template>

        <!-- 3. Icon-only or Badge + Text variant -->
        <template v-else>
            <div
                :class="[
                    'relative flex items-center justify-center overflow-hidden transition-all duration-200 shrink-0',
                    badgeClass,
                    sizeClasses.box
                ]"
            >
                <img
                    :src="iconSrc"
                    :alt="alt || 'Outreach.click'"
                    :class="[
                        'object-contain select-none transition-transform duration-200',
                        sizeClasses.img
                    ]"
                    loading="eager"
                />
            </div>

            <div v-if="showText" :class="['flex flex-col select-none', textSpacing]">
                <span :class="['font-extrabold tracking-tight leading-none', textClasses]">
                    Outreach<span :class="accentClass">.click</span>
                </span>
                <span v-if="subText" :class="['block uppercase tracking-wider font-bold mt-0.5', subTextClasses]">
                    {{ subText }}
                </span>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    size: {
        type: String,
        default: 'md', // 'xs', 'sm', 'md', 'lg', 'xl', 'auth'
    },
    variant: {
        type: String,
        default: 'auto', // 'auto', 'horizontal', 'stacked', 'icon', 'white-badge', 'transparent', 'raw'
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

const iconSrc = computed(() => {
    if (props.variant === 'transparent') {
        return '/images/logo-transparent.png'
    }
    return '/images/logo-icon.svg'
})

const horizontalSizeClass = computed(() => {
    switch (props.size) {
        case 'xs': return 'max-h-6 max-w-[130px]'
        case 'sm': return 'max-h-8 max-w-[170px]'
        case 'md': return 'max-h-10 max-w-[210px]'
        case 'lg': return 'max-h-14 max-w-[280px]'
        default: return 'max-h-9 max-w-[190px]'
    }
})

const stackedSizeClass = computed(() => {
    switch (props.size) {
        case 'sm': return 'max-h-20 max-w-[120px]'
        case 'md': return 'max-h-28 max-w-[160px]'
        case 'lg': return 'max-h-36 max-w-[210px]'
        case 'auth': return 'max-h-32 max-w-[190px]'
        default: return 'max-h-28 max-w-[160px]'
    }
})

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return { box: 'w-6 h-6', img: 'max-h-5 max-w-5' }
        case 'sm':
            return { box: 'w-9 h-9', img: 'max-h-8 max-w-8' }
        case 'md':
            return { box: 'w-11 h-11', img: 'max-h-9 max-w-9' }
        case 'lg':
            return { box: 'w-16 h-16', img: 'max-h-14 max-w-14' }
        case 'xl':
            return { box: 'w-24 h-24', img: 'max-h-20 max-w-20' }
        case 'auth':
            return { box: 'w-24 h-24 sm:w-28 sm:h-28 mx-auto', img: 'max-h-full max-w-full' }
        default:
            return { box: 'w-10 h-10', img: 'max-h-8 max-w-8' }
    }
})

const badgeClass = computed(() => {
    if (props.variant === 'raw' || props.variant === 'transparent') {
        return 'bg-transparent'
    }
    if (props.variant === 'white-badge' || props.theme === 'dark') {
        return 'bg-white rounded-xl shadow-sm p-1 ring-1 ring-white/10'
    }
    return 'bg-white rounded-xl shadow-sm p-1'
})

const textSpacing = computed(() => {
    return props.size === 'sm' || props.size === 'xs' ? 'ml-2.5' : 'ml-3'
})

const textClasses = computed(() => {
    if (props.theme === 'dark') {
        return 'text-white text-base sm:text-lg'
    }
    return 'text-gray-900 dark:text-white text-base sm:text-lg'
})

const accentClass = computed(() => {
    return props.theme === 'dark' ? 'text-sky-400' : 'text-primary-600 dark:text-primary-400'
})

const subTextClasses = computed(() => {
    return 'text-[9.5px] text-amber-400 font-bold'
})
</script>
