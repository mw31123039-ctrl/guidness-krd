<script setup>
import { computed, onUnmounted, ref, watch } from 'vue';
import { ArrowUpRight, Link as LinkIcon, MapPin, Check, Info } from '@lucide/vue';
import { useI18n } from '../i18n';
import { coordinates, parseWazeLocation, wazeEmbedUrl, wazeLocationUrl } from '../waze';

const props = defineProps({ reports: { type: Array, default: () => [] }, pickable: Boolean, position: { type: Object, default: null }, tall: Boolean });
const emit = defineEmits(['select']);
const reportId = defineModel('reportId', { default: '' });
const { t } = useI18n();
const shareLink = ref(''), linkError = ref(''), imported = ref(false), loading = ref(true), slow = ref(false);
const selectedReport = computed(() => props.reports.find(report => String(report.id) === String(reportId.value)));
const position = computed(() => {
    try {
        if (props.position) return coordinates(props.position.lat, props.position.lng);
        if (selectedReport.value) return coordinates(selectedReport.value.latitude, selectedReport.value.longitude);
    } catch { /* Incomplete manual coordinates do not create a misleading map pin. */ }
    return null;
});
const locationUrl = computed(() => wazeLocationUrl(position.value));
const targetUrl = computed(() => wazeEmbedUrl(position.value));
const iframeUrl = ref(targetUrl.value);
let updateTimer, loadingTimer;

function frameLoaded() { loading.value = false; slow.value = false; clearTimeout(loadingTimer); }
function frameFailed() { loading.value = false; slow.value = true; clearTimeout(loadingTimer); }
function startLoading() {
    loading.value = true; slow.value = false; clearTimeout(loadingTimer);
    loadingTimer = setTimeout(() => { loading.value = false; slow.value = true; }, 15000);
}
function importLocation() {
    linkError.value = ''; imported.value = false;
    try {
        const point = parseWazeLocation(shareLink.value);
        emit('select', point);
        imported.value = true;
    } catch (error) { linkError.value = error.message; }
}
watch(targetUrl, value => {
    clearTimeout(updateTimer);
    updateTimer = setTimeout(() => { startLoading(); iframeUrl.value = value; }, 350);
});
watch(() => props.reports, () => { if (reportId.value && !selectedReport.value) reportId.value = ''; });
watch(shareLink, () => { imported.value = false; linkError.value = ''; });
startLoading();
onUnmounted(() => { clearTimeout(updateTimer); clearTimeout(loadingTimer); });
</script>
<template>
    <div class="waze-widget">
        <div v-if="reports.length" class="waze-report-picker"><label>{{ t('showReportLocation') }}<select v-model="reportId"><option value="">{{ t('regionalOverview') }}</option><option v-for="report in reports" :key="report.id" :value="String(report.id)">{{ report.reference }} · {{ t(report.category) }} · {{ t(report.city) }}</option></select></label></div>
        <div class="road-map waze-map" :class="{ tall }">
            <iframe :src="iframeUrl" :title="t('wazeMapTitle')" class="waze-frame" allow="fullscreen" referrerpolicy="strict-origin-when-cross-origin" @load="frameLoaded" @error="frameFailed"></iframe>
        </div>
        <div class="waze-map-footer"><span><MapPin :size="14" />{{ position ? t('selectedLocation') : t('region') }}<span v-if="position" class="waze-coordinate" dir="ltr">{{ position.lat.toFixed(6) }}, {{ position.lng.toFixed(6) }}</span></span><a :href="locationUrl" target="_blank" rel="noopener noreferrer" class="text-link">{{ t(pickable ? 'findOnWaze' : 'openWaze') }}<ArrowUpRight :size="14" /></a></div>
        <p v-if="loading || slow" class="waze-loading" role="status">{{ t(slow ? 'wazeSlow' : 'wazeLoading') }}</p>
        <div v-if="pickable" class="waze-import">
            <p class="waze-selection-help"><Info :size="16" />{{ t('wazeSelectionHelp') }}</p>
            <div class="field waze-link-field"><label for="waze-share-link">{{ t('wazeShareLink') }}</label><span class="waze-import-row"><input id="waze-share-link" v-model="shareLink" type="url" dir="ltr" autocomplete="off" maxlength="4096" placeholder="https://www.waze.com/ul?ll=36.1911%2C44.0092" :aria-invalid="Boolean(linkError)" :class="{ invalid: linkError }" @keydown.enter.prevent="importLocation" /><button type="button" class="button secondary" @click="importLocation"><LinkIcon :size="15" />{{ t('useWazeLocation') }}</button></span></div>
            <p v-if="linkError" class="field-error" role="alert">{{ t(linkError) }}</p><p v-if="imported" class="waze-imported" role="status"><Check :size="15" />{{ t('wazeImported') }}</p>
        </div>
    </div>
</template>
