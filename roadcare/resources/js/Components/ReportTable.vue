<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, ClipboardList } from '@lucide/vue';
import StatusBadge from './StatusBadge.vue';
import { useI18n } from '../i18n';
defineProps({ reports: Array, filtered: Boolean });
const { t, date } = useI18n();
</script>
<template>
    <div v-if="reports.length" class="table-scroll"><table class="report-table"><thead><tr><th>{{ t('reference') }} / {{ t('title') }}</th><th>{{ t('city') }}</th><th>{{ t('severity') }}</th><th>{{ t('status') }}</th><th>{{ t('reported') }}</th><th><span class="sr-only">{{ t('action') }}</span></th></tr></thead><tbody><tr v-for="report in reports" :key="report.id"><td><Link :href="`/reports/${report.id}`" class="report-title">{{ report.title }}</Link><span class="report-ref">{{ report.reference }} <span>·</span> {{ t(report.category) }}</span></td><td>{{ t(report.city) }}</td><td><StatusBadge :value="report.severity" severity /></td><td><StatusBadge :value="report.status" /></td><td class="date-cell">{{ date(report.created_at) }}</td><td><Link :href="`/reports/${report.id}`" class="table-view" :aria-label="`${t('viewReport')} ${report.reference}`"><ArrowUpRight :size="18" /></Link></td></tr></tbody></table></div>
    <div v-else class="empty-state"><div class="empty-icon"><ClipboardList :size="30" /></div><h3>{{ t(filtered ? 'noMatches' : 'noReports') }}</h3><p v-if="!filtered">{{ t('noReportsText') }}</p></div>
</template>
