<script setup>
import { computed, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpRight, ShieldCheck, Clock3, Camera, MapPin, Send } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import WazeMap from '../../Components/WazeMap.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useI18n, messages } from '../../i18n';
import { wazeLocationUrl } from '../../waze';
const props = defineProps({ report: Object, transitions: Array });
const page = usePage(); const { t, date } = useI18n();
const government = computed(() => page.props.auth.user.role === 'government');
const form = useForm({ status: props.report.status, current_status: props.report.status, department: props.report.department || '', note: '' });
watch(() => props.report, report => { form.status = report.status; form.current_status = report.status; form.department = report.department || ''; });
function submit() { form.patch(`/government/reports/${props.report.id}`, { preserveScroll: true, onSuccess: () => form.reset('note') }); }
const wazeLink = computed(() => wazeLocationUrl({ lat: props.report.latitude, lng: props.report.longitude }, true));
const note = value => Object.hasOwn(messages.en, value) ? t(value) : value;
</script>
<template>
    <Head :title="report.reference" /><AppLayout><Link :href="government ? '/government/reports' : '/reports'" class="back-link"><ArrowLeft :size="16" />{{ t('backReports') }}</Link><div class="page-heading detail-heading"><div><span class="eyebrow">{{ report.reference }}</span><h1>{{ report.title }}</h1><p><MapPin :size="15" />{{ t(report.city) }} <span>·</span> {{ report.address }}</p></div><StatusBadge :value="report.status" /></div>
        <div class="detail-grid"><div class="detail-main"><section class="panel evidence-panel"><div class="panel-heading"><h2>{{ t('evidence') }}</h2><StatusBadge :value="report.severity" severity /></div><img v-if="report.photo_url" class="report-photo" :src="report.photo_url" :alt="`${t('photo')}: ${report.title}`" /><div v-else class="no-photo"><Camera :size="35" /><span>{{ t('noPhoto') }}</span></div><div class="detail-description"><h3>{{ t('description') }}</h3><p>{{ report.description }}</p><div class="detail-facts"><div><span>{{ t('category') }}</span><strong>{{ t(report.category) }}</strong></div><div><span>{{ t('submittedOn') }}</span><strong>{{ date(report.created_at) }}</strong></div><div><span>{{ t('lastUpdate') }}</span><strong>{{ date(report.updated_at) }}</strong></div><div><span>{{ t('department') }}</span><strong>{{ report.department || '—' }}</strong></div></div></div></section>
            <section class="panel timeline-panel"><div class="panel-heading"><h2>{{ t('timeline') }}</h2><Clock3 :size="18" class="muted" /></div><ol class="timeline"><li v-for="update in report.updates" :key="update.id"><span class="timeline-dot" :class="update.status"></span><div class="timeline-top"><strong>{{ t(update.status) }}</strong><time>{{ date(update.created_at) }}</time></div><p>{{ note(update.note) }}</p><span class="timeline-author">{{ t(update.by) }}</span></li></ol></section>
        </div><div class="detail-side"><section v-if="government" class="panel form-panel government-action"><div class="action-header"><ShieldCheck :size="21" /><h2>{{ t('governmentAction') }}</h2></div><form @submit.prevent="submit"><label class="field">{{ t('updateStatus') }}<select v-model="form.status"><option :value="report.status">{{ t(report.status) }}</option><option v-for="status in transitions" :key="status" :value="status">{{ t(status) }}</option></select><span v-if="form.errors.status" class="field-error">{{ form.errors.status }}</span></label><label class="field">{{ t('department') }}<input v-model="form.department" :placeholder="t('departmentPlaceholder')" maxlength="100" /><span v-if="form.errors.department" class="field-error">{{ form.errors.department }}</span></label><label class="field">{{ t('publicNote') }} *<textarea v-model="form.note" :placeholder="t('notePlaceholder')" rows="4" maxlength="2000"></textarea><span v-if="form.errors.note" class="field-error">{{ form.errors.note }}</span><small class="field-hint">{{ t('noteHelp') }}</small></label><button class="button primary full-width" :disabled="form.processing"><Send :size="16" />{{ t(form.processing ? 'saving' : 'save') }}</button></form></section>
            <section class="panel location-detail"><div class="panel-heading"><h2>{{ t('location') }}</h2><MapPin :size="18" class="cyan" /></div><WazeMap :position="{ lat: report.latitude, lng: report.longitude }" /><div class="location-detail-text"><strong>{{ report.address }}</strong><p>{{ t(report.city) }}, {{ t('region') }}</p><span class="coordinates" dir="ltr">{{ report.latitude.toFixed(6) }}, {{ report.longitude.toFixed(6) }}</span><a :href="wazeLink" target="_blank" rel="noopener noreferrer" class="text-link">{{ t('navigateWaze') }}<ArrowUpRight :size="15" /></a></div></section>
            <section v-if="government && report.citizen" class="panel contact-panel"><h2>{{ t('citizenDetails') }}</h2><strong>{{ report.citizen.name }}</strong><a :href="`mailto:${report.citizen.email}`">{{ report.citizen.email }}</a><span v-if="report.citizen.phone" dir="ltr">{{ report.citizen.phone }}</span></section>
        </div></div>
    </AppLayout>
</template>
