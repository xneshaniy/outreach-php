<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

const mobileMenuOpen = ref(false)
const page = usePage()
const user = computed(() => page.props.auth?.user)

const openFaq = ref(null)
const toggleFaq = (i) => { openFaq.value = openFaq.value === i ? null : i }

const features = [
    {
        title: 'SMTP Account Management',
        desc: 'Connect unlimited SMTP accounts. Rotate senders automatically to protect deliverability and maximise inbox rates.',
        color: 'blue',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`,
    },
    {
        title: 'Automated Campaigns',
        desc: 'Build multi-step email sequences that run on autopilot. Set it once and watch your outreach scale without manual effort.',
        color: 'purple',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`,
    },
    {
        title: 'Lead Management',
        desc: 'Import, organise and enrich your prospect lists. Keep every lead status up-to-date across all your campaigns.',
        color: 'indigo',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>`,
    },
    {
        title: 'Smart Scheduling',
        desc: 'Send emails at the perfect time for each timezone. Respect business hours and optimise send windows automatically.',
        color: 'cyan',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },
    {
        title: 'Automated Follow-ups',
        desc: 'Never let a prospect go cold. Trigger personalised follow-up sequences automatically based on engagement signals.',
        color: 'violet',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>`,
    },
    {
        title: 'Campaign Analytics',
        desc: 'Real-time dashboards showing open rates, click rates, reply rates and campaign health metrics at a glance.',
        color: 'emerald',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
    },
    {
        title: 'Daily Sending Limits',
        desc: 'Set per-account and per-campaign daily caps to stay within ESP limits and protect your sender reputation.',
        color: 'amber',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`,
    },
    {
        title: 'Duplicate Lead Protection',
        desc: 'Intelligent deduplication ensures no prospect receives the same message twice, keeping your brand professional.',
        color: 'rose',
        icon: `<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>`,
    },
]

const steps = [
    {
        num: '01',
        title: 'Connect Your SMTP Accounts',
        desc: 'Add one or multiple SMTP email accounts in seconds. Our platform rotates senders automatically to maximise deliverability.',
        tag: 'Setup in minutes',
    },
    {
        num: '02',
        title: 'Import Your Leads',
        desc: 'Upload CSV files or sync directly from Google Sheets. Our duplicate-detection engine cleans your list automatically.',
        tag: 'Instant import',
    },
    {
        num: '03',
        title: 'Create Email Campaigns',
        desc: 'Write personalised email sequences with our intuitive campaign builder. Add as many follow-up steps as you need.',
        tag: 'No-code builder',
    },
    {
        num: '04',
        title: 'Launch & Track Performance',
        desc: 'Hit send and monitor open rates, replies, and conversions in real time. Optimise your campaigns with live analytics.',
        tag: 'Real-time analytics',
    },
]

const whyItems = [
    {
        title: 'Built for Simplicity',
        desc: 'No bloated UI. Clean workflows that let you run sophisticated outreach campaigns in minutes, not hours.',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l14 9-14 9V3z"/></svg>`,
    },
    {
        title: 'Full Automation',
        desc: 'From scheduling to follow-ups, every step is automated. Your campaigns run 24/7 even when you\'re away.',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>`,
    },
    {
        title: 'SMTP Flexibility',
        desc: 'Use any SMTP provider — Gmail, Outlook, Zoho, SendGrid or your own mail server. Total control over your infrastructure.',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`,
    },
    {
        title: 'Campaign Management',
        desc: 'Manage dozens of campaigns across multiple workspaces. Track every account\'s performance from a single dashboard.',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
    },
]

const pricingPlans = [
    {
        name: 'Starter',
        price: '$29',
        period: '/month',
        desc: 'Perfect for freelancers and solo outreach specialists.',
        features: ['3 SMTP accounts', '2,000 leads', '5 active campaigns', 'Basic analytics', 'CSV import', 'Email support'],
        cta: 'Start Free Trial',
        highlight: false,
    },
    {
        name: 'Professional',
        price: '$79',
        period: '/month',
        desc: 'Ideal for growing sales teams and agencies.',
        features: ['15 SMTP accounts', '25,000 leads', 'Unlimited campaigns', 'Advanced analytics', 'Google Sheets sync', 'Auto follow-ups', 'Priority support', 'Custom sending limits'],
        cta: 'Get Started',
        highlight: true,
    },
    {
        name: 'Agency',
        price: '$199',
        period: '/month',
        desc: 'Built for high-volume agencies managing multiple clients.',
        features: ['Unlimited SMTP accounts', 'Unlimited leads', 'Unlimited campaigns', 'Multi-workspace', 'White-label ready', 'Full API access', 'Dedicated account manager', 'SLA guarantee'],
        cta: 'Contact Sales',
        highlight: false,
    },
]

const faqs = [
    {
        q: 'Which SMTP providers are supported?',
        a: 'Outreach.click works with any SMTP-compatible email provider including Gmail (with App Passwords), Microsoft Outlook/365, Zoho Mail, SendGrid, Mailgun, Amazon SES, and your own custom mail server. If it has an SMTP port, it works.',
    },
    {
        q: 'How do sending limits protect my domain reputation?',
        a: 'You can set daily sending caps at both the account level and campaign level. This ensures you never exceed your ESP\'s thresholds and gradually warm up new email accounts — a critical practice for maintaining a healthy sender score.',
    },
    {
        q: 'Can I schedule campaigns for specific times and timezones?',
        a: 'Yes. Our smart scheduling engine lets you define sending windows (e.g. weekdays 9am–5pm) and respects the recipient\'s local timezone automatically, so emails arrive during peak business hours for maximum open rates.',
    },
    {
        q: 'How does automated follow-up work?',
        a: 'You define follow-up steps in your campaign sequence (e.g. "Send follow-up 3 days after no reply"). The platform automatically sends each step only to leads who haven\'t replied, stopping the sequence as soon as a reply is detected.',
    },
    {
        q: 'Is my lead data safe?',
        a: 'Absolutely. Your lead data is stored securely and is never shared with third parties. Each workspace is fully isolated, and you retain full ownership and control over all your prospect data.',
    },
    {
        q: 'How does Google Sheets import work?',
        a: 'Paste your Google Sheets URL and we\'ll pull in your leads automatically. The sheet must be publicly readable (view-only share link). Columns are mapped to lead fields like name, email, company and phone.',
    },
]

const colorMap = {
    blue:   { bg: 'bg-blue-50',   icon: 'text-blue-600',   border: 'border-blue-100' },
    purple: { bg: 'bg-purple-50', icon: 'text-purple-600', border: 'border-purple-100' },
    indigo: { bg: 'bg-indigo-50', icon: 'text-indigo-600', border: 'border-indigo-100' },
    cyan:   { bg: 'bg-cyan-50',   icon: 'text-cyan-600',   border: 'border-cyan-100' },
    violet: { bg: 'bg-violet-50', icon: 'text-violet-600', border: 'border-violet-100' },
    emerald:{ bg: 'bg-emerald-50',icon: 'text-emerald-600',border: 'border-emerald-100' },
    amber:  { bg: 'bg-amber-50',  icon: 'text-amber-600',  border: 'border-amber-100' },
    rose:   { bg: 'bg-rose-50',   icon: 'text-rose-600',   border: 'border-rose-100' },
}

const stats = [
    { label: 'Emails Sent Daily', value: '2M+' },
    { label: 'Active Campaigns', value: '12K+' },
    { label: 'Avg. Open Rate', value: '42%' },
    { label: 'Customer Satisfaction', value: '98%' },
]
</script>

<template>
    <Head>
        <title>Outreach.click — Email Outreach Automation Platform</title>
        <meta name="description" content="Powerful email outreach automation to manage SMTP accounts, launch campaigns, reach prospects and grow your business with Outreach.click.">
        <meta property="og:title" content="Outreach.click — Email Outreach Automation Platform">
        <meta property="og:description" content="Manage SMTP accounts, automate email campaigns, import leads and track performance. The complete outreach platform for B2B sales teams.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="https://outreach.click/">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Outreach.click — Email Outreach Automation">
        <meta name="twitter:description" content="Find more leads. Send smarter emails. Grow your business.">
        <link rel="canonical" href="https://outreach.click/">
    </Head>

    <div class="min-h-screen bg-white font-sans antialiased">

        <!-- ═══════════════════════════════ HEADER ═══════════════════════════════ -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">

                    <!-- Logo -->
                    <Link :href="route('home')" class="flex items-center gap-2 flex-shrink-0">
                        <AppLogo variant="horizontal" theme="light" size="sm" />
                    </Link>

                    <!-- Desktop Nav -->
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="#features" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors">Features</a>
                        <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors">How It Works</a>
                        <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-blue-600 transition-colors">Pricing</a>
                    </nav>

                    <!-- CTA Buttons -->
                    <div class="hidden md:flex items-center gap-3">
                        <template v-if="user">
                            <Link :href="route('dashboard')"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Go to Dashboard
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')"
                                class="text-sm font-medium text-slate-700 hover:text-blue-600 transition-colors px-3 py-2">
                                Login
                            </Link>
                            <Link :href="route('login')"
                                class="inline-flex items-center px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-sm">
                                Get Started Free
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile hamburger -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors">
                        <svg v-if="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
                <div v-if="mobileMenuOpen" class="md:hidden border-t border-slate-100 bg-white px-4 py-4 space-y-2">
                    <a href="#features" @click="mobileMenuOpen=false" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">Features</a>
                    <a href="#how-it-works" @click="mobileMenuOpen=false" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">How It Works</a>
                    <a href="#pricing" @click="mobileMenuOpen=false" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">Pricing</a>
                    <div class="pt-2 border-t border-slate-100">
                        <template v-if="user">
                            <Link :href="route('dashboard')" class="block w-full text-center px-4 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-semibold">Go to Dashboard</Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="block px-3 py-2 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50">Login</Link>
                            <Link :href="route('login')" class="block w-full text-center mt-2 px-4 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white text-sm font-semibold">Get Started Free</Link>
                        </template>
                    </div>
                </div>
            </transition>
        </header>

        <!-- ═══════════════════════════════ HERO ═══════════════════════════════ -->
        <section class="relative pt-24 pb-20 lg:pt-32 lg:pb-28 overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/20">
            <!-- Background decorations -->
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute top-20 left-1/4 w-96 h-96 bg-blue-200/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-1/4 w-80 h-80 bg-purple-200/25 rounded-full blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

                    <!-- Left: Headline -->
                    <div class="text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold tracking-wide mb-6">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                            Email Outreach Automation Platform
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight tracking-tight mb-6">
                            Find More Leads.<br>
                            <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Send Smarter</span><br>
                            Emails.
                        </h1>

                        <p class="text-lg text-slate-600 leading-relaxed mb-8 max-w-lg mx-auto lg:mx-0">
                            Powerful email outreach automation to manage SMTP accounts, launch campaigns, reach prospects and grow your business — all from one platform.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start">
                            <Link :href="route('login')"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold text-base hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Start Free
                            </Link>
                            <a href="#features"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border-2 border-slate-200 text-slate-700 font-semibold text-base hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 transition-all">
                                Explore Features
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </a>
                        </div>

                        <!-- Mini trust badges -->
                        <div class="flex flex-wrap gap-4 mt-8 justify-center lg:justify-start">
                            <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                No credit card required
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Setup in 5 minutes
                            </div>
                            <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Cancel anytime
                            </div>
                        </div>
                    </div>

                    <!-- Right: Dashboard Preview -->
                    <div class="relative">
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl shadow-slate-200/80 border border-slate-200 bg-white">
                            <!-- Mock browser bar -->
                            <div class="flex items-center gap-1.5 px-4 py-3 bg-slate-50 border-b border-slate-100">
                                <span class="w-3 h-3 rounded-full bg-red-400"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                                <div class="flex-1 mx-4 bg-white border border-slate-200 rounded-md px-3 py-1 text-xs text-slate-400 font-mono">outreach.click/dashboard</div>
                            </div>
                            <!-- Dashboard body -->
                            <div class="p-5 bg-slate-50/50">
                                <!-- Stats row -->
                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">Emails Sent Today</p>
                                        <p class="text-2xl font-bold text-slate-900">1,284</p>
                                        <p class="text-xs text-emerald-600 font-medium mt-1">↑ 12% vs yesterday</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">Active SMTP Accounts</p>
                                        <p class="text-2xl font-bold text-slate-900">8</p>
                                        <p class="text-xs text-blue-600 font-medium mt-1">All healthy</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">Open Rate</p>
                                        <p class="text-2xl font-bold text-slate-900">43.2%</p>
                                        <p class="text-xs text-emerald-600 font-medium mt-1">↑ Above avg</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                                        <p class="text-xs text-slate-500 mb-1">Reply Rate</p>
                                        <p class="text-2xl font-bold text-slate-900">7.8%</p>
                                        <p class="text-xs text-purple-600 font-medium mt-1">↑ Strong</p>
                                    </div>
                                </div>
                                <!-- Campaign activity -->
                                <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                                    <p class="text-xs font-semibold text-slate-700 mb-3">Campaign Activity</p>
                                    <div class="space-y-2">
                                        <div v-for="(bar, i) in [85, 62, 91, 48, 74, 88, 55]" :key="i" class="flex items-center gap-2">
                                            <div class="text-xs text-slate-400 w-5 text-right">{{ ['M','T','W','T','F','S','S'][i] }}</div>
                                            <div class="flex-1 bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                                <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-700"
                                                    :style="`width:${bar}%`"></div>
                                            </div>
                                            <div class="text-xs text-slate-500 w-8">{{ bar }}%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Floating badge -->
                        <div class="absolute -bottom-4 -left-4 bg-white rounded-xl shadow-lg border border-slate-100 px-4 py-3 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">Campaign Launched</p>
                                <p class="text-xs text-slate-500">Q4 Outreach · 340 leads</p>
                            </div>
                        </div>
                        <div class="absolute -top-4 -right-4 bg-white rounded-xl shadow-lg border border-slate-100 px-4 py-3 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">New Reply</p>
                                <p class="text-xs text-slate-500">john@acme.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ STATS / TRUSTED BY ═══════════════════════════════ -->
        <section class="py-14 bg-white border-y border-slate-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-center text-sm font-semibold text-slate-400 uppercase tracking-widest mb-10">Platform Performance</p>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                    <div v-for="stat in stats" :key="stat.label">
                        <p class="text-4xl font-extrabold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">{{ stat.value }}</p>
                        <p class="text-sm text-slate-500 mt-1 font-medium">{{ stat.label }}</p>
                    </div>
                </div>
                <!-- Tech compatibility row -->
                <div class="mt-12 border-t border-slate-100 pt-10">
                    <p class="text-center text-xs font-semibold text-slate-400 uppercase tracking-widest mb-6">Works with any SMTP provider</p>
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <div v-for="badge in ['Gmail', 'Outlook', 'Zoho Mail', 'SendGrid', 'Mailgun', 'Amazon SES', 'Custom SMTP']" :key="badge"
                            class="px-4 py-2 rounded-full border border-slate-200 bg-slate-50 text-slate-600 text-xs font-semibold">
                            {{ badge }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ FEATURES ═══════════════════════════════ -->
        <section id="features" class="py-20 lg:py-28 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold mb-4">
                        Everything you need
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">
                        All the tools for smarter outreach
                    </h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        From SMTP management to automated follow-ups, Outreach.click gives your sales team every tool to run high-converting cold email campaigns at scale.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="feat in features" :key="feat.title"
                        :class="['group relative rounded-2xl p-6 border transition-all duration-300 hover:-translate-y-1 hover:shadow-lg cursor-default', colorMap[feat.color].bg, colorMap[feat.color].border]">
                        <div :class="['w-11 h-11 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110 bg-white shadow-sm', colorMap[feat.color].icon]"
                            v-html="feat.icon"></div>
                        <h3 class="text-sm font-bold text-slate-900 mb-2">{{ feat.title }}</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">{{ feat.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ HOW IT WORKS ═══════════════════════════════ -->
        <section id="how-it-works" class="py-20 lg:py-28 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/20 text-blue-300 text-xs font-semibold mb-4">
                        Simple 4-step process
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">How Outreach.click works</h2>
                    <p class="text-lg text-slate-300 max-w-2xl mx-auto">
                        Get your first cold email campaign live in under 10 minutes. No complicated setup. No technical expertise required.
                    </p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(step, i) in steps" :key="step.num"
                        class="group relative bg-white/5 border border-white/10 rounded-2xl p-6 hover:bg-white/10 transition-all duration-300 hover:-translate-y-1">
                        <!-- Connector line (desktop) -->
                        <div v-if="i < steps.length - 1" class="hidden lg:block absolute top-10 right-0 w-6 h-0.5 bg-gradient-to-r from-blue-500 to-purple-500 translate-x-3"></div>
                        <div class="text-5xl font-black bg-gradient-to-br from-blue-400 to-purple-400 bg-clip-text text-transparent mb-4 leading-none">{{ step.num }}</div>
                        <h3 class="text-base font-bold text-white mb-2">{{ step.title }}</h3>
                        <p class="text-sm text-slate-400 leading-relaxed mb-4">{{ step.desc }}</p>
                        <span class="inline-block px-2.5 py-1 rounded-full bg-blue-500/20 border border-blue-500/30 text-blue-300 text-xs font-medium">{{ step.tag }}</span>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <Link :href="route('login')"
                        class="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-900/50 hover:-translate-y-0.5">
                        Get Started Free
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ DASHBOARD PREVIEW ═══════════════════════════════ -->
        <section class="py-20 lg:py-28 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-purple-50 border border-purple-100 text-purple-700 text-xs font-semibold mb-4">
                        Product Preview
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Your command centre for outreach</h2>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        Monitor every campaign, account and lead from a single, beautifully designed dashboard.
                    </p>
                </div>

                <!-- Expanded dashboard mockup -->
                <div class="rounded-2xl overflow-hidden shadow-2xl shadow-slate-200 border border-slate-200 bg-white max-w-5xl mx-auto">
                    <!-- Browser chrome -->
                    <div class="flex items-center gap-2 px-5 py-3.5 bg-slate-100 border-b border-slate-200">
                        <span class="w-3 h-3 rounded-full bg-red-400"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                        <div class="flex-1 mx-4 bg-white border border-slate-200 rounded-md px-3 py-1.5 text-xs text-slate-400 font-mono">outreach.click/dashboard</div>
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">U</div>
                    </div>

                    <div class="flex h-80">
                        <!-- Sidebar -->
                        <div class="w-48 bg-slate-900 flex flex-col py-4 px-3 gap-1 shrink-0">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider px-2 mb-2">Menu</div>
                            <div v-for="item in ['Dashboard','Campaigns','Leads','SMTP Accounts','Analytics','Settings']" :key="item"
                                :class="['px-3 py-2 rounded-lg text-xs font-medium transition-colors', item === 'Dashboard' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800']">
                                {{ item }}
                            </div>
                        </div>

                        <!-- Main content -->
                        <div class="flex-1 p-5 bg-slate-50 overflow-hidden">
                            <!-- Top row KPIs -->
                            <div class="grid grid-cols-4 gap-3 mb-4">
                                <div v-for="kpi in [{label:'Emails Sent',val:'12,840',delta:'+8%',color:'blue'},{label:'Open Rate',val:'43.2%',delta:'+5%',color:'emerald'},{label:'Reply Rate',val:'7.8%',delta:'+2%',color:'purple'},{label:'Active Leads',val:'3,420',delta:'+14%',color:'amber'}]" :key="kpi.label"
                                    class="bg-white rounded-xl p-3 border border-slate-100">
                                    <p class="text-xs text-slate-500">{{ kpi.label }}</p>
                                    <p class="text-lg font-bold text-slate-900 mt-0.5">{{ kpi.val }}</p>
                                    <p :class="['text-xs font-medium mt-0.5', `text-${kpi.color}-600`]">{{ kpi.delta }}</p>
                                </div>
                            </div>

                            <!-- Campaign table preview -->
                            <div class="bg-white rounded-xl border border-slate-100 overflow-hidden">
                                <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between">
                                    <p class="text-xs font-semibold text-slate-700">Active Campaigns</p>
                                    <span class="text-xs text-blue-600 font-medium">View all →</span>
                                </div>
                                <div class="divide-y divide-slate-50">
                                    <div v-for="row in [{name:'Q4 Enterprise Push',status:'Running',leads:340,opens:'45%'},{name:'SaaS Decision Makers',status:'Running',leads:220,opens:'38%'},{name:'Follow-up Sequence B',status:'Scheduled',leads:180,opens:'—'}]" :key="row.name"
                                        class="flex items-center justify-between px-4 py-2.5">
                                        <div>
                                            <p class="text-xs font-semibold text-slate-800">{{ row.name }}</p>
                                            <p class="text-xs text-slate-500 mt-0.5">{{ row.leads }} leads</p>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-xs text-slate-600">{{ row.opens }} open</span>
                                            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', row.status === 'Running' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700']">{{ row.status }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ WHY CHOOSE ═══════════════════════════════ -->
        <section class="py-20 lg:py-28 bg-gradient-to-br from-blue-50 via-white to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold mb-5">
                            Why Outreach.click
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-5">
                            The simplest way to scale your email outreach
                        </h2>
                        <p class="text-lg text-slate-600 mb-8">
                            We built Outreach.click because existing tools were either too complex or too expensive. Our platform gives you enterprise-grade outreach automation at a price that makes sense.
                        </p>
                        <Link :href="route('login')"
                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold hover:from-blue-700 hover:to-purple-700 transition-all shadow-md hover:-translate-y-0.5">
                            Start for Free
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </Link>
                    </div>

                    <div class="space-y-4">
                        <div v-for="item in whyItems" :key="item.title"
                            class="flex items-start gap-4 bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shrink-0" v-html="item.icon"></div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 mb-1">{{ item.title }}</h3>
                                <p class="text-sm text-slate-600 leading-relaxed">{{ item.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ PRICING ═══════════════════════════════ -->
        <section id="pricing" class="py-20 lg:py-28 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-semibold mb-4">
                        Simple, transparent pricing
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Choose the plan that fits your needs</h2>
                    <p class="text-lg text-slate-600 max-w-xl mx-auto">
                        Start free, scale as you grow. No hidden fees. Cancel anytime.
                    </p>
                </div>

                <div class="grid sm:grid-cols-3 gap-6 max-w-5xl mx-auto">
                    <div v-for="plan in pricingPlans" :key="plan.name"
                        :class="['relative rounded-2xl p-7 border-2 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl', plan.highlight ? 'border-blue-500 bg-gradient-to-br from-blue-600 to-purple-700 text-white shadow-xl shadow-blue-500/25' : 'border-slate-200 bg-white hover:border-blue-200']">
                        <!-- Most popular badge -->
                        <div v-if="plan.highlight" class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                            <span class="px-4 py-1 rounded-full bg-gradient-to-r from-amber-400 to-orange-400 text-white text-xs font-bold shadow-md whitespace-nowrap">
                                ⭐ Most Popular
                            </span>
                        </div>

                        <div :class="['text-sm font-bold mb-2', plan.highlight ? 'text-blue-200' : 'text-blue-600']">{{ plan.name }}</div>
                        <div class="flex items-baseline gap-1 mb-2">
                            <span :class="['text-4xl font-extrabold', plan.highlight ? 'text-white' : 'text-slate-900']">{{ plan.price }}</span>
                            <span :class="['text-sm', plan.highlight ? 'text-blue-200' : 'text-slate-500']">{{ plan.period }}</span>
                        </div>
                        <p :class="['text-sm mb-6 leading-relaxed', plan.highlight ? 'text-blue-100' : 'text-slate-500']">{{ plan.desc }}</p>

                        <ul class="space-y-2.5 mb-7">
                            <li v-for="f in plan.features" :key="f" class="flex items-center gap-2.5 text-sm">
                                <svg :class="['w-4 h-4 shrink-0', plan.highlight ? 'text-blue-200' : 'text-emerald-500']" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                <span :class="plan.highlight ? 'text-blue-50' : 'text-slate-600'">{{ f }}</span>
                            </li>
                        </ul>

                        <Link :href="route('login')"
                            :class="['block w-full text-center py-3 rounded-xl font-semibold text-sm transition-all', plan.highlight ? 'bg-white text-blue-600 hover:bg-blue-50' : 'bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700']">
                            {{ plan.cta }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ FAQ ═══════════════════════════════ -->
        <section class="py-20 lg:py-28 bg-slate-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold mb-4">
                        Got questions?
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4">Frequently asked questions</h2>
                    <p class="text-lg text-slate-600">Everything you need to know about Outreach.click.</p>
                </div>

                <div class="space-y-3">
                    <div v-for="(faq, i) in faqs" :key="faq.q"
                        class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <button @click="toggleFaq(i)"
                            class="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-slate-50 transition-colors">
                            <span class="text-sm font-semibold text-slate-900 pr-4">{{ faq.q }}</span>
                            <svg :class="['w-5 h-5 text-blue-600 shrink-0 transition-transform duration-300', openFaq === i ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 max-h-0" enter-to-class="opacity-100 max-h-96" leave-active-class="transition-all duration-200" leave-from-class="opacity-100 max-h-96" leave-to-class="opacity-0 max-h-0">
                            <div v-if="openFaq === i" class="px-6 pb-5">
                                <p class="text-sm text-slate-600 leading-relaxed">{{ faq.a }}</p>
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════════════════════════ FINAL CTA ═══════════════════════════════ -->
        <section class="py-20 lg:py-28 bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-5">
                    Ready to scale your<br>
                    <span class="bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">email outreach?</span>
                </h2>
                <p class="text-lg text-slate-300 mb-8 max-w-xl mx-auto">
                    Join thousands of sales teams and agencies using Outreach.click to fill their pipelines with warm prospects.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link :href="route('login')"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-base hover:from-blue-600 hover:to-purple-700 transition-all shadow-xl shadow-blue-900/50 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Get Started Free
                    </Link>
                    <a href="#features"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-white/20 text-white font-semibold text-base hover:bg-white/10 transition-all">
                        Explore Features
                    </a>
                </div>
                <p class="text-slate-500 text-xs mt-6">No credit card required. Setup in under 5 minutes.</p>
            </div>
        </section>

        <!-- ═══════════════════════════════ FOOTER ═══════════════════════════════ -->
        <footer class="bg-slate-950 text-slate-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-10">
                    <!-- Brand -->
                    <div class="col-span-1 lg:col-span-2">
                        <Link :href="route('home')" class="inline-block mb-4">
                            <AppLogo variant="horizontal" theme="dark" size="sm" />
                        </Link>
                        <p class="text-sm text-slate-500 leading-relaxed max-w-sm mt-3">
                            Powerful email outreach automation to manage SMTP accounts, launch campaigns and grow your business.
                        </p>
                        <div class="flex gap-3 mt-5">
                            <a href="mailto:support@outreach.click" class="flex items-center gap-2 text-xs text-slate-500 hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                support@outreach.click
                            </a>
                        </div>
                    </div>

                    <!-- Product -->
                    <div>
                        <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">Product</p>
                        <ul class="space-y-2.5">
                            <li><a href="#features" class="text-sm text-slate-500 hover:text-white transition-colors">Features</a></li>
                            <li><a href="#how-it-works" class="text-sm text-slate-500 hover:text-white transition-colors">How It Works</a></li>
                            <li><a href="#pricing" class="text-sm text-slate-500 hover:text-white transition-colors">Pricing</a></li>
                            <li><Link :href="route('login')" class="text-sm text-slate-500 hover:text-white transition-colors">Login</Link></li>
                            <li><Link :href="route('login')" class="text-sm text-slate-500 hover:text-white transition-colors">Get Started Free</Link></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <p class="text-xs font-semibold text-slate-300 uppercase tracking-wider mb-4">Company</p>
                        <ul class="space-y-2.5">
                            <li><a href="#" class="text-sm text-slate-500 hover:text-white transition-colors">Privacy Policy</a></li>
                            <li><a href="#" class="text-sm text-slate-500 hover:text-white transition-colors">Terms of Service</a></li>
                            <li><a href="mailto:support@outreach.click" class="text-sm text-slate-500 hover:text-white transition-colors">Contact Us</a></li>
                        </ul>
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-7 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-xs text-slate-600">&copy; {{ new Date().getFullYear() }} Outreach.click. All rights reserved.</p>
                    <p class="text-xs text-slate-700">Built for B2B sales teams &amp; agencies worldwide.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
