<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

const mobileMenuOpen = ref(false)
const page = usePage()
const user = computed(() => page.props.auth?.user)

const services = [
    {
        title: 'B2B Lead Generation',
        description: 'End-to-end prospecting systems that fill your sales pipeline with verified decision-makers ready to buy.',
        badge: 'High Intent',
        icon: 'users',
    },
    {
        title: 'Targeted Prospect Research',
        description: 'Deep firmographic and technographic intelligence to identify accounts matching your ideal customer profile (ICP).',
        badge: 'Data-Driven',
        icon: 'search',
    },
    {
        title: 'Cold Email Outreach',
        description: 'Personalised, human-sounding multi-channel email sequences engineered for industry-leading response rates.',
        badge: 'High Conversion',
        icon: 'mail',
    },
    {
        title: 'Email Campaign Management',
        description: 'Full inbox rotation, warmup protocols, deliverability monitoring, and domain reputation protection.',
        badge: '99% Inbox Rate',
        icon: 'shield',
    },
    {
        title: 'Lead List Building',
        description: 'Custom-built prospect lists verified in real-time with corporate email addresses, phone numbers, and LinkedIn profiles.',
        badge: 'Verified Data',
        icon: 'database',
    },
    {
        title: 'Decision-Maker Identification',
        description: 'Pinpoint C-level executives, VPs, and directors at target enterprises with verified direct-dial contacts.',
        badge: 'Precision B2B',
        icon: 'target',
    },
]

const steps = [
    {
        number: '01',
        title: 'Understand Your Business',
        description: 'We analyze your offer, ideal customer profile (ICP), past wins, and unique value proposition to craft the winning narrative.',
        tag: 'Strategy & ICP Mapping',
    },
    {
        number: '02',
        title: 'Find Your Ideal Customers',
        description: 'Using advanced B2B databases and AI intelligence, we build hyper-targeted prospect lists verified for maximum accuracy.',
        tag: 'Data Enrichment & Verification',
    },
    {
        number: '03',
        title: 'Launch Targeted Outreach',
        description: 'We deploy sophisticated multi-step cold email campaigns through warmed infrastructure with advanced personalization.',
        tag: 'Automated Multi-Channel Sequences',
    },
    {
        number: '04',
        title: 'Generate More Opportunities',
        description: 'Interested leads drop straight into your calendar or CRM, letting your sales team focus purely on closing deals.',
        tag: 'Booked Meetings & Pipeline Growth',
    },
]

const whyChooseUs = [
    {
        title: 'Targeted Prospect Research',
        description: 'Zero guesswork. Every prospect is thoroughly vetted against your exact ICP criteria before outreach begins.',
        icon: 'crosshair',
    },
    {
        title: 'Personalised Outreach',
        description: 'Bespoke messaging tailored to each prospect’s role, company news, and industry pain points.',
        icon: 'sparkles',
    },
    {
        title: 'Quality-Focused Lead Generation',
        description: 'We prioritize high-value enterprise and mid-market accounts over low-converting bulk volume.',
        icon: 'badge-check',
    },
    {
        title: 'Transparent Communication',
        description: 'Real-time pipeline visibility, detailed campaign analytics, and weekly performance reporting.',
        icon: 'chart',
    },
    {
        title: 'Inbox Deliverability Management',
        description: 'Full technical handling of SPF, DKIM, DMARC records, mailbox rotation, and ongoing warmup.',
        icon: 'inbox',
    },
    {
        title: 'Dedicated Growth Strategist',
        description: 'Dedicated growth team working alongside your sales leadership to scale outbound revenue sustainably.',
        icon: 'trending',
    },
]

const industries = [
    {
        name: 'SaaS & Technology',
        desc: 'Scaling ARR for B2B software, cloud, and AI innovators.',
        icon: 'code',
    },
    {
        name: 'Marketing & Digital Agencies',
        desc: 'Consistent, predictable client acquisition pipelines.',
        icon: 'megaphone',
    },
    {
        name: 'Real Estate & PropTech',
        desc: 'Connecting developers and asset managers with institutional buyers.',
        icon: 'building',
    },
    {
        name: 'E-commerce & D2C Brands',
        desc: 'Wholesale, distribution, and B2B vendor partnership generation.',
        icon: 'cart',
    },
    {
        name: 'Professional & Legal Services',
        desc: 'High-ticket advisory, accounting, and consulting client acquisition.',
        icon: 'briefcase',
    },
    {
        name: 'Financial & FinTech',
        desc: 'Connecting wealth managers, funds, and B2B finance providers.',
        icon: 'coins',
    },
]

const techIntegrations = [
    { name: 'Google Workspace', label: 'Gmail', icon: 'gmail' },
    { name: 'Microsoft 365', label: 'Outlook', icon: 'outlook' },
    { name: 'HubSpot CRM', label: 'HubSpot', icon: 'hubspot' },
    { name: 'Salesforce', label: 'Salesforce', icon: 'salesforce' },
    { name: 'LinkedIn Sales Navigator', label: 'LinkedIn', icon: 'linkedin' },
    { name: 'Zapier & Webhooks', label: 'Zapier', icon: 'zapier' },
]
</script>

<template>
    <Head title="Outreach — B2B Lead Generation & Cold Email Outreach Agency" />

    <div class="min-h-screen bg-slate-950 text-slate-50 font-sans selection:bg-blue-600 selection:text-white">
        <!-- AMBIENT GLOWS -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1100px] h-[550px] bg-blue-600/15 blur-[140px] rounded-full"></div>
            <div class="absolute top-[35%] left-[5%] w-[650px] h-[450px] bg-sky-500/10 blur-[130px] rounded-full"></div>
            <div class="absolute top-[70%] right-[5%] w-[750px] h-[500px] bg-indigo-600/10 blur-[150px] rounded-full"></div>
        </div>

        <div class="relative z-10">
            <!-- HEADER / NAVIGATION -->
            <header class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/85 border-b border-slate-800/80">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <!-- Brand Logo -->
                    <Link :href="route('home')" class="flex items-center gap-3 group focus:outline-none">
                        <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-blue-600/30 via-slate-900 to-slate-950 border border-blue-500/30 p-2 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:border-blue-400 transition-all duration-300">
                            <!-- Official Outreach.click Vector Hand & Click Waves -->
                            <svg class="w-full h-full" viewBox="0 0 160 160" fill="none">
                                <defs>
                                    <linearGradient id="hdrHandGrad" x1="0%" y1="100%" x2="70%" y2="0%">
                                        <stop offset="0%" stop-color="#0242A6" />
                                        <stop offset="55%" stop-color="#0265D2" />
                                        <stop offset="100%" stop-color="#0284C7" />
                                    </linearGradient>
                                    <linearGradient id="hdrWaveGrad" x1="0%" y1="100%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#0284C7" />
                                        <stop offset="60%" stop-color="#0EA5E9" />
                                        <stop offset="100%" stop-color="#38BDF8" />
                                    </linearGradient>
                                    <filter id="hdrWaveGlow" x="-20%" y="-20%" width="140%" height="140%">
                                        <feDropShadow dx="0" dy="1.5" stdDeviation="2.5" flood-color="#0EA5E9" flood-opacity="0.3" />
                                    </filter>
                                </defs>
                                <g filter="url(#hdrWaveGlow)" stroke="url(#hdrWaveGrad)" stroke-linecap="round" fill="none">
                                    <path d="M 82 50 A 13 13 0 0 1 104 50" stroke-width="3.5" />
                                    <path d="M 74 44 A 23 23 0 0 1 112 44" stroke-width="3.5" />
                                    <path d="M 66 38 A 33 33 0 0 1 120 38" stroke-width="3.5" />
                                </g>
                                <g fill="url(#hdrHandGrad)">
                                    <path d="M 89 54 C 89 50 98 50 98 54 L 97 90 C 97 92 95 93 93 93 C 91 93 89 92 89 90 Z" />
                                    <path d="M 83 66 C 83 62 88 62 88 66 L 88 84 C 88 85.5 86.5 87 85 87 C 83.5 87 83 85.5 83 84 Z" />
                                    <path d="M 77 72 C 77 69 82 69 82 72 L 82 86 C 82 87.5 80 89 78.5 89 C 77 89 77 87.5 77 86 Z" />
                                    <path d="M 71 78 C 71 75 76 75 76 78 L 76 91 C 76 92.5 74 94 72.5 94 C 71 94 71 92.5 71 91 Z" />
                                    <path d="M 99 82 C 99 79 102 78 105 79.5 C 110 83 115 88 113.5 93 C 112 96 107 95 104 92.5 C 101.5 90 99 86 99 82 Z" />
                                    <path d="M 74 98 C 79 97 87 95 95 97 C 102 99.5 107 103 105 106 C 103.5 108.5 98 107.5 92 104.5 C 86 102 78 103 73 104.5 C 71 104.5 71 99.5 74 98 Z" />
                                    <path d="M 71 110 C 77 108.5 85 107 93 109 C 100 111 103 114 101 117 C 98.5 119.5 93 118 87 115.5 C 81 113 74 114.5 70 116 C 68 116 68 111.5 71 110 Z" />
                                    <path d="M 69 121 C 75 119.5 82 118 89 120 C 95 122 97.5 125 95 127.5 C 92.5 130 88 128.5 82 126 C 77 124 72 125.5 67 127 C 65 127 65 122.5 69 121 Z" />
                                    <path d="M 68 132 C 73 130.5 80 129 86 131 C 91 132.5 93 135.5 90.5 138 C 88 140 84 139 79 137 C 74 135 70 136.5 66 138 C 64 138 64 133.5 68 132 Z" />
                                </g>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl sm:text-2xl font-black tracking-tight text-white leading-tight">
                                Outreach<span class="bg-gradient-to-r from-sky-400 to-blue-500 bg-clip-text text-transparent">.click</span>
                            </span>
                            <span class="text-[10px] font-bold tracking-wider uppercase text-slate-400 -mt-0.5">B2B Growth Platform</span>
                        </div>
                    </Link>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden lg:flex items-center gap-8 text-sm font-medium text-slate-300">
                        <a href="#services" class="hover:text-white transition-colors">Services</a>
                        <a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a>
                        <a href="#why-us" class="hover:text-white transition-colors">Why Choose Us</a>
                        <a href="#industries" class="hover:text-white transition-colors">Industries</a>
                        <a href="#integrations" class="hover:text-white transition-colors">Integrations</a>
                        <a href="#contact" class="hover:text-white transition-colors">Contact</a>
                    </nav>

                    <!-- User / Auth CTA Actions -->
                    <div class="hidden sm:flex items-center gap-3.5">
                        <template v-if="user">
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 border border-slate-700 hover:border-blue-500 text-sm font-semibold text-white transition-all shadow-xs"
                            >
                                <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>Go to Dashboard</span>
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-sm font-semibold text-slate-300 hover:text-white px-3.5 py-2 rounded-lg hover:bg-slate-900 transition-colors"
                            >
                                Client Sign In
                            </Link>
                            <a
                                href="#contact"
                                class="relative inline-flex items-center justify-center p-0.5 overflow-hidden text-sm font-bold text-white rounded-xl group bg-gradient-to-r from-blue-600 to-sky-500 shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 transition-shadow"
                            >
                                <span class="relative px-4 py-2 transition-all ease-in duration-150 bg-slate-950 rounded-[10px] group-hover:bg-opacity-0 flex items-center gap-1.5">
                                    <span>Get More Clients</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </span>
                            </a>
                        </template>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="lg:hidden p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 focus:outline-none"
                        aria-label="Toggle Navigation Menu"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile Menu Dropdown -->
                <div v-show="mobileMenuOpen" class="lg:hidden border-b border-slate-800 bg-slate-950/95 backdrop-blur-2xl px-4 py-6 space-y-4">
                    <a href="#services" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Services</a>
                    <a href="#how-it-works" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">How It Works</a>
                    <a href="#why-us" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Why Choose Us</a>
                    <a href="#industries" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Industries</a>
                    <a href="#integrations" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Integrations</a>
                    <a href="#contact" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Contact</a>
                    <div class="pt-4 border-t border-slate-800 flex flex-col gap-3">
                        <template v-if="user">
                            <Link :href="route('dashboard')" class="w-full text-center py-2.5 rounded-xl bg-blue-600 font-semibold text-white">
                                Go to Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="w-full text-center py-2 text-sm font-semibold text-slate-300 border border-slate-800 rounded-xl">
                                Client Sign In
                            </Link>
                            <a href="#contact" @click="mobileMenuOpen = false" class="w-full text-center py-2.5 rounded-xl bg-blue-600 font-semibold text-white shadow-lg shadow-blue-600/30">
                                Get More Clients
                            </a>
                        </template>
                    </div>
                </div>
            </header>

            <!-- HERO SECTION -->
            <section class="relative pt-16 sm:pt-24 pb-24 sm:pb-32 overflow-hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                    <div class="text-center max-w-4xl mx-auto space-y-8">
                        <!-- Eyebrow Badge -->
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-semibold tracking-wide uppercase shadow-inner">
                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                            <span>B2B Lead Generation & Cold Email Outreach Agency</span>
                        </div>

                        <!-- Main Headline -->
                        <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white leading-[1.08]">
                            We Find Your Next Clients. <br class="hidden sm:inline" />
                            <span class="bg-gradient-to-r from-blue-400 via-sky-300 to-indigo-400 bg-clip-text text-transparent">
                                You Focus on Growing Your Business.
                            </span>
                        </h1>

                        <!-- Subtitle -->
                        <p class="text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto font-normal leading-relaxed">
                            We help B2B companies find verified decision-makers, scale personalised cold email outreach, and consistently fill their sales pipelines with ready-to-buy prospects.
                        </p>

                        <!-- Primary CTA Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                            <a
                                href="#contact"
                                class="w-full sm:w-auto px-8 py-4 rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-600 text-white font-bold text-base shadow-xl shadow-blue-600/30 transition-all flex items-center justify-center gap-2 group"
                            >
                                <span>Get More Clients</span>
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            <a
                                href="#services"
                                class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-900/90 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-slate-200 font-bold text-base transition-all flex items-center justify-center"
                            >
                                Explore Our Services
                            </a>
                        </div>

                        <!-- Metrics Proof Bar -->
                        <div class="pt-10 border-t border-slate-900 mt-12 grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-center">
                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-sm">
                                <div class="text-3xl sm:text-4xl font-extrabold text-white">99.2%</div>
                                <div class="text-xs text-slate-400 mt-1 font-medium">Inbox Delivery Rate</div>
                            </div>
                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-sm">
                                <div class="text-3xl sm:text-4xl font-extrabold text-blue-400">4.5x</div>
                                <div class="text-xs text-slate-400 mt-1 font-medium">Average Client ROI</div>
                            </div>
                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-sm">
                                <div class="text-3xl sm:text-4xl font-extrabold text-white">10M+</div>
                                <div class="text-xs text-slate-400 mt-1 font-medium">Verified B2B Leads</div>
                            </div>
                            <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-sm">
                                <div class="text-3xl sm:text-4xl font-extrabold text-sky-400">350+</div>
                                <div class="text-xs text-slate-400 mt-1 font-medium">Active Outbound Campaigns</div>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Engine Showcase Card -->
                    <div class="mt-16 relative max-w-5xl mx-auto rounded-2xl border border-slate-800 bg-slate-900/70 p-5 sm:p-7 shadow-2xl backdrop-blur-xl">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 mb-6 border-b border-slate-800/80 gap-3">
                            <div class="flex items-center gap-3">
                                <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></div>
                                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">The Outreach.click Outbound Engine</h2>
                            </div>
                            <span class="text-xs font-mono text-blue-400 bg-blue-500/10 px-3 py-1 rounded-full border border-blue-500/20">
                                Real-Time Prospecting to Closed Won
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="w-10 h-10 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center font-bold">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-mono text-slate-400">Step 01</span>
                                </div>
                                <h3 class="font-bold text-white text-base">ICP & Prospect Enrichment</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">Pinpoint verified executive emails and decision-maker direct-dials matching your exact target market.</p>
                                <div class="pt-2 flex items-center gap-2 text-[11px] text-emerald-400 font-mono">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> 5,000 Verified Accounts / Mo
                                </div>
                            </div>

                            <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="w-10 h-10 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-mono text-slate-400">Step 02</span>
                                </div>
                                <h3 class="font-bold text-white text-base">AI Personalised Sequences</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">Dynamic multi-touch email copy referencing prospect achievements, role priorities, and company triggers.</p>
                                <div class="pt-2 flex items-center gap-2 text-[11px] text-sky-400 font-mono">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> 42% Open Rate Benchmark
                                </div>
                            </div>

                            <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-mono text-slate-400">Step 03</span>
                                </div>
                                <h3 class="font-bold text-white text-base">Meetings Booked & Revenue</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">Positive replies automatically routed directly into your CRM and calendar, ready for discovery calls.</p>
                                <div class="pt-2 flex items-center gap-2 text-[11px] text-emerald-400 font-mono">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> 18% Meeting Conversion
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- INTEGRATIONS LOGO CLOUD -->
            <section id="integrations" class="py-12 border-y border-slate-900 bg-slate-950/60">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-xs font-semibold uppercase tracking-wider text-slate-400 mb-8">
                        Seamless integration with your enterprise email and sales tech stack
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                        <div
                            v-for="tool in techIntegrations"
                            :key="tool.name"
                            class="px-4 py-3 rounded-xl bg-slate-900/60 border border-slate-800/70 hover:border-blue-500/40 hover:bg-slate-900 transition-all flex items-center justify-center gap-2.5 group"
                        >
                            <!-- Gmail -->
                            <svg v-if="tool.icon === 'gmail'" class="w-4 h-4 text-red-400 group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                            <!-- Outlook -->
                            <svg v-else-if="tool.icon === 'outlook'" class="w-4 h-4 text-sky-400 group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21.5 4h-19C1.7 4 1 4.7 1 5.5v13c0 .8.7 1.5 1.5 1.5h19c.8 0 1.5-.7 1.5-1.5v-13c0-.8-.7-1.5-1.5-1.5zM20 7.5L12 12.8 4 7.5V6l8 5.3L20 6v1.5z"/>
                            </svg>
                            <!-- HubSpot -->
                            <svg v-else-if="tool.icon === 'hubspot'" class="w-4 h-4 text-amber-500 group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M12 2a10 10 0 0 0-7.07 17.07l1.41-1.41A8 8 0 1 1 12 20v2a10 10 0 0 0 0-20z"/>
                            </svg>
                            <!-- Salesforce -->
                            <svg v-else-if="tool.icon === 'salesforce'" class="w-4 h-4 text-blue-400 group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                            </svg>
                            <!-- LinkedIn -->
                            <svg v-else-if="tool.icon === 'linkedin'" class="w-4 h-4 text-blue-500 group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 8.76a1.65 1.65 0 1 0 0-3.3 1.65 1.65 0 0 0 0 3.3m1.4 9.74v-8.37H5.06v8.37h2.8z"/>
                            </svg>
                            <!-- Zapier -->
                            <svg v-else class="w-4 h-4 text-orange-400 group-hover:scale-110 transition-transform shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M13 2L3 14h7v8l10-12h-7l3-8z"/>
                            </svg>
                            <span class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors">{{ tool.label }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SERVICES SECTION -->
            <section id="services" class="py-24 bg-slate-900/40 border-b border-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-blue-400">World-Class Capabilities</h2>
                        <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">Our Core Services</h3>
                        <p class="text-slate-400 text-base">Comprehensive B2B outreach infrastructure engineered to scale your revenue predictably.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div
                            v-for="(s, index) in services"
                            :key="index"
                            class="p-8 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all duration-300 group hover:shadow-2xl hover:shadow-blue-500/10 flex flex-col justify-between"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-6">
                                    <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-xs">
                                        <svg v-if="s.icon === 'users'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <svg v-else-if="s.icon === 'search'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <svg v-else-if="s.icon === 'mail'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <svg v-else-if="s.icon === 'shield'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <svg v-else-if="s.icon === 'database'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                        </svg>
                                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="10" stroke-width="2" />
                                            <circle cx="12" cy="12" r="6" stroke-width="2" />
                                            <circle cx="12" cy="12" r="2" fill="currentColor" />
                                        </svg>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-blue-300 border border-slate-700">
                                        {{ s.badge }}
                                    </span>
                                </div>
                                <h4 class="text-xl font-bold text-white mb-3 group-hover:text-blue-400 transition-colors">{{ s.title }}</h4>
                                <p class="text-sm text-slate-400 leading-relaxed">{{ s.description }}</p>
                            </div>
                            <div class="pt-6 mt-6 border-t border-slate-800/80 flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-300">Enterprise Ready</span>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- HOW IT WORKS SECTION -->
            <section id="how-it-works" class="py-24 border-b border-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto space-y-4 mb-20">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-blue-400">Streamlined Process</h2>
                        <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">How Outreach Works</h3>
                        <p class="text-slate-400 text-base">A transparent, 4-step execution framework designed to deliver pipeline from day one.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div
                            v-for="(step, idx) in steps"
                            :key="idx"
                            class="relative p-8 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between hover:border-slate-700 transition-colors"
                        >
                            <div class="absolute -top-5 left-8 w-10 h-10 rounded-xl bg-blue-600 text-white font-extrabold text-sm flex items-center justify-center shadow-lg shadow-blue-600/30">
                                {{ step.number }}
                            </div>
                            <div class="pt-4">
                                <h4 class="text-lg font-bold text-white mb-3">{{ step.title }}</h4>
                                <p class="text-sm text-slate-400 leading-relaxed">{{ step.description }}</p>
                            </div>
                            <div class="pt-6 mt-6 border-t border-slate-800/60 text-xs font-mono text-blue-400 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                <span>{{ step.tag }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- WHY CHOOSE OUTREACH SECTION -->
            <section id="why-us" class="py-24 bg-slate-900/40 border-b border-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        <div class="space-y-6">
                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-semibold uppercase">
                                The Outreach.click Advantage
                            </div>
                            <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                                Why Industry Leaders Choose Outreach
                            </h3>
                            <p class="text-slate-300 text-base leading-relaxed">
                                We combine enterprise-grade lead intelligence, rigorous inbox deliverability protocols, and expert copywriting to generate consistent, qualified sales meetings.
                            </p>
                            <div class="pt-4">
                                <a
                                    href="#contact"
                                    class="px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 font-bold text-white transition-all inline-flex items-center gap-2 shadow-lg shadow-blue-600/30"
                                >
                                    <span>Schedule Strategy Call</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div
                                v-for="(item, i) in whyChooseUs"
                                :key="i"
                                class="p-6 rounded-2xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-all group"
                            >
                                <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-500/20 text-blue-400 flex items-center justify-center font-bold mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <!-- Crosshair -->
                                    <svg v-if="item.icon === 'crosshair'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                                        <line x1="22" y1="12" x2="18" y2="12" stroke-width="2"/>
                                        <line x1="6" y1="12" x2="2" y2="12" stroke-width="2"/>
                                        <line x1="12" y1="6" x2="12" y2="2" stroke-width="2"/>
                                        <line x1="12" y1="22" x2="12" y2="18" stroke-width="2"/>
                                    </svg>
                                    <!-- Sparkles -->
                                    <svg v-else-if="item.icon === 'sparkles'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                    </svg>
                                    <!-- Badge Check -->
                                    <svg v-else-if="item.icon === 'badge-check'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                    </svg>
                                    <!-- Chart -->
                                    <svg v-else-if="item.icon === 'chart'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    <!-- Inbox -->
                                    <svg v-else-if="item.icon === 'inbox'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <!-- Trending / Default -->
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <h4 class="font-bold text-white text-base mb-2 group-hover:text-blue-400 transition-colors">{{ item.title }}</h4>
                                <p class="text-xs text-slate-400 leading-relaxed">{{ item.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- INDUSTRIES SECTION -->
            <section id="industries" class="py-24 border-b border-slate-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-blue-400">Specialized Expertise</h2>
                        <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">Industries We Serve</h3>
                        <p class="text-slate-400 text-base">Tailored outbound campaigns built for high-growth sectors and B2B markets.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="(ind, idx) in industries"
                            :key="idx"
                            class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-blue-500/40 transition-all group"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-10 h-10 rounded-xl bg-blue-600/10 text-blue-400 flex items-center justify-center font-bold group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <svg v-if="ind.icon === 'code'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                    <svg v-else-if="ind.icon === 'megaphone'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                    </svg>
                                    <svg v-else-if="ind.icon === 'building'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <svg v-else-if="ind.icon === 'cart'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    <svg v-else-if="ind.icon === 'briefcase'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-xs text-slate-400 font-mono">B2B Sector</span>
                            </div>
                            <h4 class="font-bold text-white text-lg mb-2">{{ ind.name }}</h4>
                            <p class="text-xs text-slate-400 leading-relaxed">{{ ind.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FINAL CALL TO ACTION SECTION -->
            <section id="contact" class="py-24 relative overflow-hidden bg-gradient-to-b from-slate-900 to-slate-950 border-b border-slate-900">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(37,99,235,0.2),transparent_70%)] pointer-events-none"></div>
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center space-y-8">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-semibold uppercase">
                        Ready to Scale Your Sales Pipeline?
                    </div>
                    <h3 class="text-3xl sm:text-6xl font-extrabold text-white tracking-tight leading-tight">
                        Ready to Find Your Next High-Value Clients?
                    </h3>
                    <p class="text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                        Let's build a predictable outbound lead-generation system for your business. Reach out today to start acquiring verified enterprise accounts.
                    </p>
                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <template v-if="user">
                            <Link
                                :href="route('dashboard')"
                                class="w-full sm:w-auto px-9 py-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-base shadow-xl shadow-blue-600/30 transition-all flex items-center justify-center gap-2"
                            >
                                <span>Go to Dashboard</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="w-full sm:w-auto px-9 py-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-base shadow-xl shadow-blue-600/30 transition-all flex items-center justify-center gap-2"
                            >
                                <span>Client Sign In</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </Link>
                        </template>
                        <a
                            href="mailto:admin@outreach.click"
                            class="w-full sm:w-auto px-9 py-4 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-200 font-bold text-base transition-all flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>Contact Sales: admin@outreach.click</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="bg-slate-950 text-slate-400 text-sm py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                        <!-- Brand column -->
                        <div class="lg:col-span-2 space-y-4">
                            <Link :href="route('home')" class="flex items-center gap-3 group focus:outline-none">
                                <div class="h-10 w-10 rounded-2xl bg-gradient-to-br from-blue-600/30 via-slate-900 to-slate-950 border border-blue-500/30 p-2 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:border-blue-400 transition-all">
                                    <svg class="w-full h-full" viewBox="0 0 160 160" fill="none">
                                        <g filter="url(#hdrWaveGlow)" stroke="url(#hdrWaveGrad)" stroke-linecap="round" fill="none">
                                            <path d="M 82 50 A 13 13 0 0 1 104 50" stroke-width="3.5" />
                                            <path d="M 74 44 A 23 23 0 0 1 112 44" stroke-width="3.5" />
                                            <path d="M 66 38 A 33 33 0 0 1 120 38" stroke-width="3.5" />
                                        </g>
                                        <g fill="url(#hdrHandGrad)">
                                            <path d="M 89 54 C 89 50 98 50 98 54 L 97 90 C 97 92 95 93 93 93 C 91 93 89 92 89 90 Z" />
                                            <path d="M 83 66 C 83 62 88 62 88 66 L 88 84 C 88 85.5 86.5 87 85 87 C 83.5 87 83 85.5 83 84 Z" />
                                            <path d="M 77 72 C 77 69 82 69 82 72 L 82 86 C 82 87.5 80 89 78.5 89 C 77 89 77 87.5 77 86 Z" />
                                            <path d="M 71 78 C 71 75 76 75 76 78 L 76 91 C 76 92.5 74 94 72.5 94 C 71 94 71 92.5 71 91 Z" />
                                            <path d="M 99 82 C 99 79 102 78 105 79.5 C 110 83 115 88 113.5 93 C 112 96 107 95 104 92.5 C 101.5 90 99 86 99 82 Z" />
                                            <path d="M 74 98 C 79 97 87 95 95 97 C 102 99.5 107 103 105 106 C 103.5 108.5 98 107.5 92 104.5 C 86 102 78 103 73 104.5 C 71 104.5 71 99.5 74 98 Z" />
                                            <path d="M 71 110 C 77 108.5 85 107 93 109 C 100 111 103 114 101 117 C 98.5 119.5 93 118 87 115.5 C 81 113 74 114.5 70 116 C 68 116 68 111.5 71 110 Z" />
                                            <path d="M 69 121 C 75 119.5 82 118 89 120 C 95 122 97.5 125 95 127.5 C 92.5 130 88 128.5 82 126 C 77 124 72 125.5 67 127 C 65 127 65 122.5 69 121 Z" />
                                            <path d="M 68 132 C 73 130.5 80 129 86 131 C 91 132.5 93 135.5 90.5 138 C 88 140 84 139 79 137 C 74 135 70 136.5 66 138 C 64 138 64 133.5 68 132 Z" />
                                        </g>
                                    </svg>
                                </div>
                                <span class="text-xl font-black tracking-tight text-white leading-tight">
                                    Outreach<span class="bg-gradient-to-r from-sky-400 to-blue-500 bg-clip-text text-transparent">.click</span>
                                </span>
                            </Link>
                            <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                                Find More Leads. Build More Connections. Win More Clients. We help B2B enterprises and agencies scale outbound pipeline predictability.
                            </p>
                            <div class="text-xs text-slate-500 font-mono">
                                © {{ new Date().getFullYear() }} Outreach.click. All rights reserved.
                            </div>
                        </div>

                        <div>
                            <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Navigation</h4>
                            <ul class="space-y-2 text-xs">
                                <li><a href="#" class="hover:text-white transition-colors">Home</a></li>
                                <li><a href="#services" class="hover:text-white transition-colors">Services</a></li>
                                <li><a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a></li>
                                <li><a href="#why-us" class="hover:text-white transition-colors">Why Choose Us</a></li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Core Services</h4>
                            <ul class="space-y-2 text-xs">
                                <li><a href="#services" class="hover:text-white transition-colors">B2B Lead Generation</a></li>
                                <li><a href="#services" class="hover:text-white transition-colors">Prospect Research</a></li>
                                <li><a href="#services" class="hover:text-white transition-colors">Cold Email Outreach</a></li>
                                <li><a href="#services" class="hover:text-white transition-colors">Campaign Management</a></li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Client Portal</h4>
                            <ul class="space-y-2 text-xs">
                                <li><Link :href="route('login')" class="hover:text-white transition-colors">Client Sign In</Link></li>
                                <li><a href="mailto:admin@outreach.click" class="hover:text-white transition-colors">Sales: admin@outreach.click</a></li>
                                <li><a href="#contact" class="hover:text-white transition-colors">Help & Support</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="pt-8 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500">
                        <p>Powered by high-performance B2B outreach infrastructure.</p>
                        <div class="flex items-center gap-4 mt-4 sm:mt-0">
                            <Link :href="route('login')" class="hover:text-slate-400">Sign In</Link>
                            <span>·</span>
                            <a href="#contact" class="hover:text-slate-400">Privacy Policy</a>
                            <span>·</span>
                            <a href="#contact" class="hover:text-slate-400">Terms of Service</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>
