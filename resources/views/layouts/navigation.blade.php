<nav x-data="{ sidebarOpen: false }" class="fixed inset-y-0 left-0 z-50 w-64 bg-navy-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0" :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }" @keydown.escape.window="sidebarOpen = false">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center h-16 px-4 border-b border-navy-800">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
                <span class="text-xl font-bold text-white">{{ config('app.name', 'Outreach CRM') }}</span>
            </a>
        </div>

        <!-- Navigation -->
        <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                Dashboard
            </a>

            <!-- Leads -->
            <div>
                <button @click="leadsOpen = !leadsOpen" class="sidebar-link w-full text-left {{ request()->routeIs('leads.*') ? 'sidebar-link-active' : '' }}" x-data="{ leadsOpen: {{ request()->routeIs('leads.*') ? 'true' : 'false' }} }">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Leads
                    <svg class="w-4 h-4 ml-auto transform transition-transform" :class="{ 'rotate-90': leadsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="leadsOpen" x-transition class="pl-10 space-y-1">
                    <a href="{{ route('leads.index') }}" class="sidebar-link {{ request()->routeIs('leads.index') ? 'sidebar-link-active' : '' }}">All Leads</a>
                    <a href="{{ route('leads.create') }}" class="sidebar-link">Add Lead</a>
                    <a href="{{ route('leads.import.form') }}" class="sidebar-link">Import Leads</a>
                    <a href="{{ route('leads.imports.index') }}" class="sidebar-link">Import History</a>
                </div>
            </div>

            <!-- Companies -->
            <a href="{{ route('companies.index') }}" class="sidebar-link {{ request()->routeIs('companies.*') ? 'sidebar-link-active' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Companies
            </a>

            <!-- Campaigns -->
            <div>
                <button @click="campaignsOpen = !campaignsOpen" class="sidebar-link w-full text-left {{ request()->routeIs('campaigns.*') ? 'sidebar-link-active' : '' }}" x-data="{ campaignsOpen: {{ request()->routeIs('campaigns.*') ? 'true' : 'false' }} }">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Campaigns
                    <svg class="w-4 h-4 ml-auto transform transition-transform" :class="{ 'rotate-90': campaignsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="campaignsOpen" x-transition class="pl-10 space-y-1">
                    <a href="{{ route('campaigns.index') }}" class="sidebar-link {{ request()->routeIs('campaigns.index') ? 'sidebar-link-active' : '' }}">All Campaigns</a>
                    <a href="{{ route('campaigns.create') }}" class="sidebar-link">Create Campaign</a>
                </div>
            </div>

            <!-- Email Accounts -->
            <a href="{{ route('email-accounts.index') }}" class="sidebar-link {{ request()->routeIs('email-accounts.*') ? 'sidebar-link-active' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Email Accounts
            </a>

            <!-- Pipeline -->
            <a href="{{ route('pipeline.index') }}" class="sidebar-link {{ request()->routeIs('pipeline.*') ? 'sidebar-link-active' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                </svg>
                Pipeline
            </a>

            <!-- Tasks -->
            <a href="{{ route('tasks.index') }}" class="sidebar-link {{ request()->routeIs('tasks.*') ? 'sidebar-link-active' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Tasks
            </a>

            <!-- Reports -->
            <div>
                <button @click="reportsOpen = !reportsOpen" class="sidebar-link w-full text-left {{ request()->routeIs('reports.*') ? 'sidebar-link-active' : '' }}" x-data="{ reportsOpen: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Reports
                    <svg class="w-4 h-4 ml-auto transform transition-transform" :class="{ 'rotate-90': reportsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="reportsOpen" x-transition class="pl-10 space-y-1">
                    <a href="{{ route('reports.leads') }}" class="sidebar-link">Leads Report</a>
                    <a href="{{ route('reports.campaigns') }}" class="sidebar-link">Campaign Performance</a>
                    <a href="{{ route('reports.deals') }}" class="sidebar-link">Sales Pipeline</a>
                    <a href="{{ route('reports.team') }}" class="sidebar-link">Team Performance</a>
                    <a href="{{ route('reports.email') }}" class="sidebar-link">Email Analytics</a>
                </div>
            </div>
        </div>

        <!-- User Menu / Workspace Switcher -->
        <div class="p-4 border-t border-navy-800">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
                    {{ auth()->user()->name[0] ?? 'U' }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-navy-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>

            @if (auth()->user()->activeWorkspaces()->count() > 1)
            <div class="mt-3">
                <a href="{{ route('settings.workspace.switch') }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium text-navy-300 rounded-lg hover:bg-navy-800 hover:text-white transition-colors">
                    <span>{{ auth()->user()->currentWorkspace?->name ?? 'Select Workspace' }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
            @endif

            <div class="mt-3 space-y-1">
                <a href="{{ route('settings.profile') }}" class="sidebar-link">Profile</a>
                <a href="{{ route('settings.workspace') }}" class="sidebar-link">Workspace Settings</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="sidebar-link w-full text-left text-red-400 hover:bg-red-900/20">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile sidebar overlay -->
<div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>