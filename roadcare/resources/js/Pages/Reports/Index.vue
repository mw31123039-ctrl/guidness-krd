<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Search, Plus, SlidersHorizontal, ChevronLeft, ChevronRight } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ReportTable from '../../Components/ReportTable.vue';
import { useI18n } from '../../i18n';
const props = defineProps({ reports: Object, filters: Object });
const page = usePage(); const { t } = useI18n();
const government = computed(() => page.props.auth.user.role === 'government');
const filters = reactive({ search: props.filters.search || '', status: props.filters.status || '', city: props.filters.city || '', severity: props.filters.severity || '' });
const filtered = computed(() => Object.values(props.filters).some(Boolean));
function search() { router.get(government.value ? '/government/reports' : '/reports', Object.fromEntries(Object.entries(filters).filter(([, value]) => value)), { preserveState: true, preserveScroll: true, replace: true }); }
function reset() { Object.keys(filters).forEach(key => filters[key] = ''); search(); }
</script>
<template>
    <Head :title="t(government ? 'allReports' : 'myReports')" /><AppLayout><div class="page-heading"><div><span class="eyebrow">{{ t(government ? 'governmentPortal' : 'citizenPortal') }}</span><h1>{{ t(government ? 'allReports' : 'myReports') }}</h1><p>{{ t(government ? 'manageIntro' : 'reportsIntro') }}</p></div><Link v-if="!government" href="/reports/create" class="button primary"><Plus :size="18" />{{ t('reportDamage') }}</Link></div>
        <section class="panel"><form class="filters" @submit.prevent="search"><div class="search-field"><Search :size="18" /><input v-model="filters.search" :placeholder="t('searchPlaceholder')" :aria-label="t('search')" maxlength="100" /></div><select v-model="filters.status" :aria-label="t('status')" @change="search"><option value="">{{ t('allStatuses') }}</option><option v-for="status in page.props.options.statuses" :key="status" :value="status">{{ t(status) }}</option></select><select v-model="filters.city" :aria-label="t('city')" @change="search"><option value="">{{ t('allCities') }}</option><option v-for="city in page.props.options.cities" :key="city" :value="city">{{ t(city) }}</option></select><select v-model="filters.severity" :aria-label="t('severity')" @change="search"><option value="">{{ t('allSeverities') }}</option><option v-for="severity in page.props.options.severities" :key="severity" :value="severity">{{ t(severity) }}</option></select><button class="button secondary" type="submit"><SlidersHorizontal :size="16" />{{ t('search') }}</button><button v-if="filtered" type="button" class="text-link" @click="reset">{{ t('clearFilters') }}</button></form>
            <div v-if="Object.keys(page.props.errors).length" class="form-error-banner">{{ Object.values(page.props.errors).join(' ') }}</div><ReportTable :reports="reports.data" :filtered="filtered" /><div class="pagination"><span>{{ t('showing') }} {{ reports.from || 0 }}–{{ reports.to || 0 }} {{ t('of') }} {{ reports.total }}</span><div><Link v-if="reports.prev_page_url" :href="reports.prev_page_url" class="button secondary"><ChevronLeft :size="15" />{{ t('previous') }}</Link><span class="page-number">{{ reports.current_page }} / {{ reports.last_page }}</span><Link v-if="reports.next_page_url" :href="reports.next_page_url" class="button secondary">{{ t('next') }}<ChevronRight :size="15" /></Link></div></div>
        </section>
    </AppLayout>
</template>
