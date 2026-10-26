<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'

const props = defineProps({
    columns: Array,
    data: Array,
    loading: { type: Boolean, default: false },
    pagination: Object,
    sort: Object,
    selection: Array,
    selectAll: Boolean,
})

const emit = defineEmits(['sort', 'selection-change', 'select-all'])

const sortedData = computed(() => {
    if (!props.sort?.field) return props.data
    return [...props.data].sort((a, b) => {
        const aVal = a[props.sort.field]
        const bVal = b[props.sort.field]
        if (aVal < bVal) return props.sort.direction === 'asc' ? -1 : 1
        if (aVal > bVal) return props.sort.direction === 'asc' ? 1 : -1
        return 0
    })
})

const handleSort = (key) => {
    const column = props.columns.find(c => c.key === key)
    if (!column?.sortable) return

    let direction = 'asc'
    if (props.sort.field === key && props.sort.direction === 'asc') {
        direction = 'desc'
    }
    emit('sort', { field: key, direction })
}

const isSelected = (id) => props.selection.includes(id)

const handleRowSelect = (id, checked) => {
    emit('selection-change', id)
}

const handleSelectAll = (checked) => {
    emit('select-all', checked)
}

const renderCell = (row, column) => {
    const slot = column.slot || `cell-${column.key}`
    return slot
}
</script>

<template>
    <div class="card shadow-card">
        <div class="overflow-hidden rounded-lg">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th v-for="column in columns" :key="column.key" :class="column.class" :width="column.width">
                            <div class="flex items-center space-x-2">
                                <input
                                    v-if="column.key === 'select'"
                                    type="checkbox"
                                    :checked="selectAll"
                                    :indeterminate="selection.length > 0 && selection.length < data.length"
                                    @change="handleSelectAll"
                                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                />
                                <span v-else>
                                    <button
                                        v-if="column.sortable"
                                        @click="handleSort(column.key)"
                                        class="flex items-center space-x-1 hover:text-gray-700 dark:hover:text-gray-200"
                                    >
                                        <span>{{ column.label }}</span>
                                        <svg v-if="sort.field === column.key" class="w-4 h-4 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="sort.direction === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'" />
                                        </svg>
                                        <svg v-else class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18" />
                                        </svg>
                                    </button>
                                    <span v-else>{{ column.label }}</span>
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in sortedData" :key="row.id">
                        <td v-for="column in columns" :key="column.key" :class="column.class">
                            <slot :name="`cell-${column.key}`" :row="row" :index="sortedData.indexOf(row)">
                                <span v-if="column.key !== 'select' && column.key !== 'actions'">
                                    {{ row[column.key] }}
                                </span>
                            </slot>
                        </td>
                    </tr>
                    <tr v-if="sortedData.length === 0">
                        <td :colspan="columns.length" class="px-4 py-12 text-center text-muted-foreground">
                            <slot name="empty">No data available</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="px-4 py-3 border-t border-border flex items-center justify-between">
            <div class="text-sm text-muted-foreground">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
            </div>
            <div class="flex items-center space-x-2">
                <button
                    v-if="pagination.current_page > 1"
                    @click="router.get(route().current(), { ...route().params, page: pagination.current_page - 1 }, { preserveScroll: true, preserveState: true })"
                    class="btn-secondary text-sm"
                    :disabled="loading"
                >
                    Previous
                </button>
                <span class="text-sm text-muted-foreground px-2">
                    Page {{ pagination.current_page }} of {{ pagination.last_page }}
                </span>
                <button
                    v-if="pagination.current_page < pagination.last_page"
                    @click="router.get(route().current(), { ...route().params, page: pagination.current_page + 1 }, { preserveScroll: true, preserveState: true })"
                    class="btn-secondary text-sm"
                    :disabled="loading"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>