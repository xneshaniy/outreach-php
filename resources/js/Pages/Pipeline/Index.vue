<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const props = defineProps({
    stages: Array,
    groupedLeads: Object,
    totalLeads: Number,
})

// Local reactive copy of grouped leads for smooth drag and drop
const stageLeads = ref(JSON.parse(JSON.stringify(props.groupedLeads || {})))
const draggingLead = ref(null)
const dragOverStage = ref(null)
const isUpdating = ref(false)

const getScoreBadgeClass = (score) => {
    if (score >= 70) return 'badge-danger'
    if (score >= 50) return 'badge-warning'
    if (score >= 30) return 'badge-info'
    return 'badge-secondary'
}

// Drag & Drop handlers
const handleDragStart = (lead, stageId, event) => {
    draggingLead.value = { lead, fromStage: stageId }
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', lead.id)
}

const handleDragOver = (stageId, event) => {
    event.preventDefault()
    event.dataTransfer.dropEffect = 'move'
    dragOverStage.value = stageId
}

const handleDragLeave = (stageId) => {
    if (dragOverStage.value === stageId) {
        dragOverStage.value = null
    }
}

const handleDrop = async (targetStageId, event) => {
    event.preventDefault()
    dragOverStage.value = null

    if (!draggingLead.value) return

    const { lead, fromStage } = draggingLead.value
    if (fromStage === targetStageId) {
        draggingLead.value = null
        return
    }

    // Optimistically update local board state
    const sourceList = stageLeads.value[fromStage] || []
    const targetList = stageLeads.value[targetStageId] || []

    const index = sourceList.findIndex(l => l.id === lead.id)
    if (index !== -1) {
        const [movedLead] = sourceList.splice(index, 1)
        movedLead.pipeline_stage = targetStageId
        targetList.unshift(movedLead)
        stageLeads.value[fromStage] = sourceList
        stageLeads.value[targetStageId] = targetList
    }

    isUpdating.value = true

    try {
        const response = await axios.post(route('pipeline.leads.move-stage', lead.id), {
            stage: targetStageId,
        })
        if (response.data?.score !== undefined) {
            lead.score = response.data.score
            lead.category = response.data.category
        }
    } catch (err) {
        console.error('Failed to save pipeline stage change', err)
        // Rollback on error
        stageLeads.value = JSON.parse(JSON.stringify(props.groupedLeads || {}))
        alert('Failed to update stage on server. Reverting.')
    } finally {
        isUpdating.value = false
        draggingLead.value = null
    }
}
</script>

<template>
    <Head title="CRM Sales Pipeline - Kanban Board" />

    <MainLayout title="CRM Sales Pipeline" description="Visual drag-and-drop pipeline: track leads from prospect to closed deal">
        <div class="space-y-4">
            <!-- Header Summary -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Pipeline Kanban</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ totalLeads }} total prospects across 9 conversion stages. Drag cards to advance pipeline stages.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="isUpdating" class="text-xs text-primary-600 font-medium animate-pulse flex items-center">
                        <svg class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving stage changes...
                    </span>
                    <Link :href="route('leads.create')" class="btn-primary text-xs py-1.5 px-3 inline-flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Lead
                    </Link>
                </div>
            </div>

            <!-- Kanban Board (9 columns horizontal scroll) -->
            <div class="flex gap-3 overflow-x-auto pb-6 min-h-[calc(100vh-210px)] select-none">
                <div
                    v-for="stage in stages"
                    :key="stage.id"
                    @dragover="handleDragOver(stage.id, $event)"
                    @dragleave="handleDragLeave(stage.id)"
                    @drop="handleDrop(stage.id, $event)"
                    class="w-72 flex-shrink-0 bg-gray-100/80 dark:bg-gray-800/60 rounded-xl p-3 flex flex-col transition-colors border-2"
                    :class="dragOverStage === stage.id ? 'border-primary-500 bg-primary-50/20' : 'border-transparent'"
                >
                    <!-- Stage Header -->
                    <div class="flex items-center justify-between pb-2 mb-2 border-b dark:border-gray-700">
                        <div class="flex items-center space-x-2">
                            <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: stage.color }"></div>
                            <h3 class="font-bold text-xs text-gray-900 dark:text-white uppercase tracking-wider">
                                {{ stage.name }}
                            </h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 shadow-xs">
                            {{ stageLeads[stage.id]?.length || 0 }}
                        </span>
                    </div>

                    <!-- Cards Container -->
                    <div class="space-y-2.5 flex-1 overflow-y-auto pr-1">
                        <div
                            v-for="lead in (stageLeads[stage.id] || [])"
                            :key="lead.id"
                            draggable="true"
                            @dragstart="handleDragStart(lead, stage.id, $event)"
                            class="card p-3 shadow-xs hover:shadow-md cursor-grab active:cursor-grabbing transition-all bg-white dark:bg-gray-800 border hover:border-primary-400 group"
                        >
                            <!-- Card Header: Name & Score -->
                            <div class="flex items-start justify-between gap-1 mb-1.5">
                                <Link
                                    :href="route('leads.show', lead.id)"
                                    class="font-bold text-xs text-gray-900 dark:text-white hover:text-primary-600 line-clamp-1"
                                >
                                    {{ lead.name }}
                                </Link>
                                <span :class="['badge text-[10px] px-1.5 py-0.5', getScoreBadgeClass(lead.score)]" title="Lead Score">
                                    {{ lead.score }} pts
                                </span>
                            </div>

                            <!-- Company -->
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mb-2">
                                🏢 {{ lead.company }}
                            </p>

                            <!-- Deal Value if applicable -->
                            <div v-if="lead.deal_value" class="mb-2 px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center justify-between">
                                <span>Deal Value:</span>
                                <span>${{ Number(lead.deal_value).toLocaleString() }}</span>
                            </div>

                            <!-- Campaign / Follow up metadata -->
                            <div class="pt-2 border-t dark:border-gray-700/60 space-y-1 text-[10px] text-gray-400">
                                <div v-if="lead.campaign_name" class="flex items-center justify-between truncate">
                                    <span>Campaign:</span>
                                    <span class="font-medium text-gray-600 dark:text-gray-300 truncate max-w-[120px]">{{ lead.campaign_name }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Last Contact:</span>
                                    <span>{{ lead.last_contacted_at }}</span>
                                </div>
                                <div v-if="lead.follow_up_at" class="flex items-center justify-between text-amber-600 dark:text-amber-400 font-medium">
                                    <span>Follow-up:</span>
                                    <span>{{ lead.follow_up_at }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State in Stage -->
                        <div
                            v-if="!stageLeads[stage.id] || stageLeads[stage.id].length === 0"
                            class="h-24 border border-dashed rounded-lg flex items-center justify-center text-xs text-gray-400 dark:border-gray-700"
                        >
                            Drop leads here
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
