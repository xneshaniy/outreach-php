export function resolveTheme(theme) {
    if (theme === 'dark') return 'dark'
    if (theme === 'system' && typeof window !== 'undefined') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }
    return 'light'
}

export function applyTheme(theme) {
    if (typeof document === 'undefined') return

    const resolved = resolveTheme(theme)
    document.documentElement.classList.toggle('dark', resolved === 'dark')
    document.documentElement.style.colorScheme = resolved

    try {
        localStorage.setItem('theme', theme || 'light')
    } catch (e) {
        // Ignore private-mode storage errors.
    }
}

export function getStoredTheme() {
    try {
        return localStorage.getItem('theme') || 'light'
    } catch (e) {
        return 'light'
    }
}
