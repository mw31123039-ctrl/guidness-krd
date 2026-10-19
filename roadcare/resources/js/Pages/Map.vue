<script setup>
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import AppLayout from '../Layouts/AppLayout.vue';
import WazeMap from '../Components/WazeMap.vue';
import StatusBadge from '../Components/StatusBadge.vue';
import { useI18n } from '../i18n';
const props = defineProps({ mapReports: Array });
const page = usePage(); const { t } = useI18n();
const severity = ref(''), city = ref(''), status = ref('active'), selectedReportId = ref('');
const filtered = computed(() => props.mapReports.filter(report => (!severity.value || report.severity === severity.value) && (!city.value || report.city === city.value) && (status.value !== 'active' || report.status !== 'resolved')));
</script>
<template>
    <Head :title="t('roadMap')" /><AppLayout><div class="page-heading"><div><span class="eyebrow">{{ t('region') }} <span class="cyan">/</span> {{ t('roadMap') }}</span><h1>{{ t('mapTitle') }}</h1><p>{{ t('mapIntro') }}</p></div></div><div class="map-page-grid"><section class="panel full-map-panel"><div class="map-filters"><div class="segmented"><button @click="status = 'active'" :class="{ selected: status === 'active' }">{{ t('active') }}</button><button @click="status = ''" :class="{ selected: !status }">{{ t('all') }}</button></div><select v-model="city" :aria-label="t('city')"><option value="">{{ t('allCities') }}</option><option v-for="city in page.props.options.cities" :key="city" :value="city">{{ t(city) }}</option></select><select v-model="severity" :aria-label="t('severity')"><option value="">{{ t('allSeverities') }}</option><option v-for="severity in page.props.options.severities" :key="severity" :value="severity">{{ t(severity) }}</option></select></div><WazeMap :reports="filtered" v-model:report-id="selectedReportId" tall /></section><section class="panel hazard-list-panel"><div class="panel-heading"><h2>{{ t('listedHazards') }}</h2><span class="count-pill">{{ filtered.length }}</span></div><div class="hazard-scroll"><article v-for="report in filtered" :key="report.id" class="hazard-item"><div class="hazard-item-top"><span class="hazard-category"><MapPin :size="16" />{{ t(report.category) }}</span><StatusBadge :value="report.severity" severity /></div><strong>{{ report.reference }}</strong><span>{{ t(report.city) }} · {{ t(report.status) }}</span><button type="button" class="text-link hazard-map-button" :aria-pressed="String(selectedReportId) === String(report.id)" @click="selectedReportId = String(report.id)">{{ t('showOnMap') }}</button></article><div v-if="!filtered.length" class="empty-state"><MapPin :size="26" /><p>{{ t('noHazards') }}</p></div></div></section></div></AppLayout>
</template>
