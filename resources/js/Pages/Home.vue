<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

const mobileMenuOpen = ref(false)

const services = [
    {
        title: 'B2B Lead Generation',
        description: 'End-to-end prospecting systems that fill your sales pipeline with verified decision-makers ready to buy.',
        icon: 'Users',
        badge: 'High Intent'
    },
    {
        title: 'Targeted Prospect Research',
        description: 'Deep firmographic and technographic intelligence to identify accounts matching your ideal customer profile (ICP).',
        icon: 'Search',
        badge: 'Data-Driven'
    },
    {
        title: 'Cold Email Outreach',
        description: 'Personalised, human-sounding multi-channel email sequences engineered for industry-leading response rates.',
        icon: 'Mail',
        badge: 'High Conversion'
    },
    {
        title: 'Email Campaign Management',
        description: 'Full inbox rotation, warmup protocols, deliverability monitoring, and domain reputation protection.',
        icon: 'ShieldCheck',
        badge: '99% Inbox Rate'
    },
    {
        title: 'Lead List Building',
        description: 'Custom-built prospect lists verified in real-time with verified corporate email addresses and phone numbers.',
        icon: 'Database',
        badge: 'Verified Data'
    },
    {
        title: 'Decision-Maker Identification',
        description: 'Pinpoint C-level executives, VPs, and directors at target enterprises with verified direct-dial contacts.',
        icon: 'Target',
        badge: 'Precision B2B'
    }
]

const steps = [
    {
        number: '01',
        title: 'Understand Your Business',
        description: 'We analyze your offer, ideal customer profile (ICP), past wins, and unique value proposition to craft the winning narrative.'
    },
    {
        number: '02',
        title: 'Find Your Ideal Customers',
        description: 'Using advanced B2B databases and AI intelligence, we build hyper-targeted prospect lists verified for maximum accuracy.'
    },
    {
        number: '03',
        title: 'Launch Targeted Outreach',
        description: 'We deploy sophisticated multi-step cold email campaigns through warmed infrastructure with advanced personalization.'
    },
    {
        number: '04',
        title: 'Generate More Opportunities',
        description: 'Interested leads drop straight into your calendar or CRM, letting your sales team focus purely on closing deals.'
    }
]

const whyChooseUs = [
    {
        title: 'Targeted Prospect Research',
        description: 'Zero guesswork. Every prospect is thoroughly vetted against your exact ICP criteria before outreach begins.'
    },
    {
        title: 'Personalised Outreach',
        description: 'Bespoke messaging tailored to each prospect’s role, company news, and industry pain points.'
    },
    {
        title: 'Quality-Focused Lead Generation',
        description: 'We prioritize high-value enterprise and mid-market accounts over low-converting bulk volume.'
    },
    {
        title: 'Transparent Communication',
        description: 'Real-time pipeline visibility, detailed campaign analytics, and weekly performance reporting.'
    },
    {
        title: 'Campaign Management',
        description: 'Full technical handling of DNS records, DKIM/SPF, mailbox rotation, and ongoing optimization.'
    },
    {
        title: 'Business Growth Support',
        description: 'Dedicated growth strategists working alongside your sales leadership to scale revenue sustainably.'
    }
]

const industries = [
    { name: 'SaaS & Technology', icon: 'Cpu', desc: 'Scaling ARR for B2B software and cloud innovators.' },
    { name: 'Marketing Agencies', icon: 'Megaphone', desc: 'Consistent, predictable client acquisition pipelines.' },
    { name: 'Real Estate', icon: 'Building2', desc: 'Connecting commercial and residential developers with buyers.' },
    { name: 'E-commerce & D2C', icon: 'ShoppingBag', desc: 'Wholesale and retail partnership generation.' },
    { name: 'Professional Services', icon: 'Briefcase', desc: 'High-ticket advisory and consulting client acquisition.' },
    { name: 'B2B Companies', icon: 'Globe', desc: 'Expanding market share across North America and Europe.' }
]
</script>

<template>
    <Head title="Outreach — B2B Lead Generation & Email Outreach Agency" />

    <div class="min-h-screen bg-slate-950 text-slate-50 font-sans selection:bg-blue-600 selection:text-white">
        <!-- BACKGROUND GLOWS -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[550px] bg-blue-600/15 blur-[140px] rounded-full"></div>
            <div class="absolute top-[40%] left-[10%] w-[600px] h-[400px] bg-sky-500/10 blur-[120px] rounded-full"></div>
            <div class="absolute top-[70%] right-[10%] w-[700px] h-[500px] bg-blue-700/10 blur-[150px] rounded-full"></div>
        </div>

        <!-- SECTION 1: NAVIGATION -->
        <header class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19V5m-7 7l7-7 7 7"/>
                        </svg>
                    </div>
                    <span class="text-xl font-extrabold tracking-tight text-white">
                        Outreach<span class="text-blue-500">.click</span>
                    </span>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#services" class="hover:text-white transition-colors">Services</a>
                    <a href="#how-it-works" class="hover:text-white transition-colors">How It Works</a>
                    <a href="#why-us" class="hover:text-white transition-colors">Why Us</a>
                    <a href="#industries" class="hover:text-white transition-colors">Industries</a>
                    <a href="#contact" class="hover:text-white transition-colors">Contact</a>
                </nav>

                <!-- CTA Actions -->
                <div class="hidden md:flex items-center gap-4">
                    <Link :href="route('login')" class="text-sm font-semibold text-slate-300 hover:text-white transition-colors">
                        Client Sign In
                    </Link>
                    <a href="#contact" class="relative group overflow-hidden rounded-xl p-px font-semibold text-sm">
                        <span class="absolute inset-0 bg-gradient-to-r from-blue-500 to-sky-400 transition-all group-hover:opacity-90"></span>
                        <span class="relative px-5 py-2.5 rounded-[11px] bg-slate-950 flex items-center gap-2 text-white transition-colors group-hover:bg-transparent">
                            <span>Get More Clients</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Dropdown -->
            <div v-if="mobileMenuOpen" class="md:hidden border-b border-slate-800 bg-slate-950/95 px-4 py-6 space-y-4">
                <a href="#services" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Services</a>
                <a href="#how-it-works" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">How It Works</a>
                <a href="#why-us" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Why Us</a>
                <a href="#industries" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Industries</a>
                <a href="#contact" @click="mobileMenuOpen = false" class="block text-base font-medium text-slate-300 hover:text-white">Contact</a>
                <div class="pt-4 border-t border-slate-800 flex flex-col gap-3">
                    <Link :href="route('login')" class="w-full text-center py-2.5 text-sm font-semibold text-slate-300">Client Sign In</Link>
                    <a href="#contact" @click="mobileMenuOpen = false" class="w-full text-center py-3 rounded-xl bg-blue-600 font-semibold text-white shadow-lg shadow-blue-600/30">Get More Clients</a>
                </div>
            </div>
        </header>

        <!-- SECTION 2: HERO -->
        <section class="relative pt-20 pb-32 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="text-center max-w-4xl mx-auto space-y-8">
                    <!-- Badge -->
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-semibold tracking-wide uppercase">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        B2B Lead Generation & Email Outreach Agency
                    </div>

                    <!-- Main Headline -->
                    <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white leading-[1.1]">
                        We Find Your Next Clients. <br class="hidden sm:inline">
                        <span class="bg-gradient-to-r from-blue-400 via-sky-300 to-blue-500 bg-clip-text text-transparent">
                            You Focus on Growing Your Business.
                        </span>
                    </h1>

                    <!-- Description -->
                    <p class="text-lg sm:text-xl text-slate-300 max-w-2xl mx-auto font-normal leading-relaxed">
                        We help businesses find qualified prospects, connect with decision-makers, and generate more customers through targeted B2B lead generation and personalised email outreach.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4 pt-4">
                        <a href="#contact" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-base shadow-xl shadow-blue-600/30 transition-all flex items-center justify-center gap-2 group">
                            <span>Get More Clients</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </a>
                        <a href="#services" class="w-full sm:w-auto px-8 py-4 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-200 font-bold text-base transition-all flex items-center justify-center">
                            Explore Our Services
                        </a>
                    </div>

                    <!-- Trust Bar -->
                    <div class="pt-12 border-t border-slate-900 mt-16 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                        <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60">
                            <div class="text-3xl font-extrabold text-white">99.2%</div>
                            <div class="text-xs text-slate-400 mt-1">Inbox Delivery Rate</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60">
                            <div class="text-3xl font-extrabold text-blue-400">4.5x</div>
                            <div class="text-xs text-slate-400 mt-1">Average Pipeline ROI</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60">
                            <div class="text-3xl font-extrabold text-white">10M+</div>
                            <div class="text-xs text-slate-400 mt-1">Verified B2B Leads</div>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60">
                            <div class="text-3xl font-extrabold text-sky-400">350+</div>
                            <div class="text-xs text-slate-400 mt-1">Active Client Campaigns</div>
                        </div>
                    </div>
                </div>

                <!-- Hero Workflow Visual Preview -->
                <div class="mt-16 relative max-w-5xl mx-auto rounded-2xl border border-slate-800 bg-slate-900/80 p-4 sm:p-6 shadow-2xl backdrop-blur-xl">
                    <div class="absolute -top-3 left-6 px-3 py-1 bg-blue-600 text-white text-[11px] font-bold rounded-full uppercase tracking-wider">
                        Live Outreach Workflow Engine
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
                        <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">01</div>
                            <div class="font-bold text-white text-sm">ICP & Data Enrichment</div>
                            <p class="text-xs text-slate-400">Filtering decision-makers by industry, technology stack, and funding signals.</p>
                            <div class="pt-2 flex items-center gap-2 text-[11px] text-emerald-400 font-mono">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> 5,000 Verified Accounts
                            </div>
                        </div>
                        <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                            <div class="w-9 h-9 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center font-bold">02</div>
                            <div class="font-bold text-white text-sm">AI Personalised Sequences</div>
                            <p class="text-xs text-slate-400">Multi-channel email copy tailored dynamically to each executive role.</p>
                            <div class="pt-2 flex items-center gap-2 text-[11px] text-sky-400 font-mono">
                                <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span> 42% Open Rate Benchmark
                            </div>
                        </div>
                        <div class="p-5 rounded-xl bg-slate-950 border border-slate-800/80 space-y-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">03</div>
                            <div class="font-bold text-white text-sm">Calendar Bookings & Deals</div>
                            <p class="text-xs text-slate-400">Qualified replies seamlessly routed to your sales team calendar and pipeline.</p>
                            <div class="pt-2 flex items-center gap-2 text-[11px] text-emerald-400 font-mono">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> 18% Meeting Conversion
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 3: SERVICES -->
        <section id="services" class="py-24 bg-slate-900/50 border-t border-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-blue-400">World-Class Capabilities</h2>
                    <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">Our Core Services</h3>
                    <p class="text-slate-400 text-base">Comprehensive B2B outreach infrastructure designed to scale your revenue predictably.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="(s, index) in services" :key="index" class="p-8 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 transition-all duration-300 group hover:shadow-2xl hover:shadow-blue-500/10 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-12 h-12 rounded-xl bg-blue-600/20 border border-blue-500/30 text-blue-400 flex items-center justify-center font-bold text-lg group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    0{{ index + 1 }}
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-slate-800 text-blue-300 border border-slate-700">
                                    {{ s.badge }}
                                </span>
                            </div>
                            <h4 class="text-xl font-bold text-white mb-3 group-hover:text-blue-400 transition-colors">{{ s.title }}</h4>
                            <p class="text-sm text-slate-400 leading-relaxed">{{ s.description }}</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-800/80 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Learn more</span>
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 4: HOW IT WORKS -->
        <section id="how-it-works" class="py-24 border-t border-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-20">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-blue-400">Streamlined Process</h2>
                    <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">How Outreach Works</h3>
                    <p class="text-slate-400 text-base">A transparent, 4-step execution framework designed to deliver pipeline from day one.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="(step, idx) in steps" :key="idx" class="relative p-8 rounded-2xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
                        <div class="absolute -top-5 left-8 w-10 h-10 rounded-xl bg-blue-600 text-white font-extrabold text-sm flex items-center justify-center shadow-lg shadow-blue-600/30">
                            {{ step.number }}
                        </div>
                        <div class="pt-4">
                            <h4 class="text-lg font-bold text-white mb-3">{{ step.title }}</h4>
                            <p class="text-sm text-slate-400 leading-relaxed">{{ step.description }}</p>
                        </div>
                        <div class="pt-6 mt-6 border-t border-slate-800/60 text-xs font-mono text-blue-400 flex items-center gap-1.5">
                            <span>Phase {{ step.number }} Execution</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 5: WHY CHOOSE OUTREACH? -->
        <section id="why-us" class="py-24 bg-slate-900/50 border-t border-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div class="space-y-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-semibold uppercase">
                            The Outreach Advantage
                        </div>
                        <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight leading-tight">
                            Why Industry Leaders Choose Outreach
                        </h3>
                        <p class="text-slate-300 text-base leading-relaxed">
                            We combine enterprise-grade lead intelligence, rigorous inbox deliverability protocols, and expert copywriting to generate consistent, qualified sales meetings.
                        </p>
                        <div class="pt-4">
                            <a href="#contact" class="px-7 py-3.5 rounded-xl bg-blue-600 hover:bg-blue-500 font-bold text-white transition-all inline-flex items-center gap-2 shadow-lg shadow-blue-600/30">
                                <span>Schedule Strategy Call</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="(item, i) in whyChooseUs" :key="i" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-all">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold mb-4">
                                ✓
                            </div>
                            <h4 class="font-bold text-white text-base mb-2">{{ item.title }}</h4>
                            <p class="text-xs text-slate-400 leading-relaxed">{{ item.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 6: INDUSTRIES WE SERVE -->
        <section id="industries" class="py-24 border-t border-slate-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-blue-400">Specialized Expertise</h2>
                    <h3 class="text-3xl sm:text-5xl font-extrabold text-white tracking-tight">Industries We Serve</h3>
                    <p class="text-slate-400 text-base">Tailored outbound campaigns built for high-growth sectors and B2B markets.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="(ind, idx) in industries" :key="idx" class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-blue-500/40 transition-all group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-600/10 text-blue-400 flex items-center justify-center font-bold group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                ❖
                            </div>
                            <span class="text-xs text-slate-500 font-mono">Verified Sector</span>
                        </div>
                        <h4 class="font-bold text-white text-lg mb-2">{{ ind.name }}</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">{{ ind.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 7: FINAL CTA -->
        <section id="contact" class="py-24 relative overflow-hidden bg-gradient-to-b from-slate-900 to-slate-950 border-t border-slate-900">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(37,99,235,0.2),transparent_70%)] pointer-events-none"></div>
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center space-y-8">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full border border-blue-500/30 bg-blue-500/10 text-blue-400 text-xs font-semibold uppercase">
                    Ready to Scale?
                </div>
                <h3 class="text-3xl sm:text-6xl font-extrabold text-white tracking-tight leading-tight">
                    Ready to Find Your Next Customers?
                </h3>
                <p class="text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                    Let's build a smarter lead-generation strategy for your business. Book a discovery call today and get your custom outreach blueprint.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('login')" class="w-full sm:w-auto px-9 py-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-base shadow-xl shadow-blue-600/30 transition-all flex items-center justify-center gap-2">
                        <span>Start Your Growth Journey</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </Link>
                    <a href="mailto:admin@outreach.click" class="w-full sm:w-auto px-9 py-4 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-200 font-bold text-base transition-all flex items-center justify-center">
                        Contact Sales: admin@outreach.click
                    </a>
                </div>
            </div>
        </section>

        <!-- SECTION 8: FOOTER -->
        <footer class="bg-slate-950 border-t border-slate-900 text-slate-400 text-sm py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 mb-12">
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold">
                                <span>O</span>
                            </div>
                            <span class="text-lg font-bold text-white">Outreach<span class="text-blue-500">.click</span></span>
                        </div>
                        <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
                            Find More Leads. Build More Connections. Win More Clients. We help B2B companies scale through targeted lead generation and email outreach.
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
                            <li><a href="#why-us" class="hover:text-white transition-colors">Why Us</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold text-white text-xs uppercase tracking-wider mb-4">Services</h4>
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
                            <li><a href="#contact" class="hover:text-white transition-colors">Support & Contact</a></li>
                            <li><a href="mailto:admin@outreach.click" class="hover:text-white transition-colors">admin@outreach.click</a></li>
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
</template>
