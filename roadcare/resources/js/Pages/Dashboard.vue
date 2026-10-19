<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Plus, ArrowUpRight, FileText, Clock3, TriangleAlert, CircleCheck, Camera, MapPin, CheckCheck, ShieldCheck as ShieldIcon } from '@lucide/vue';
import AppLayout from '../Layouts/AppLayout.vue';
import WazeMap from '../Components/WazeMap.vue';
import ReportTable from '../Components/ReportTable.vue';
import { useI18n } from '../i18n';
defineProps({ stats: Object, recentReports: Array, mapReports: Array, cityStats: Array });
const page = usePage(); const { t } = useI18n();
const government = computed(() => page.props.auth.user.role === 'government');
const cards = [{ key: 'total', label: 'totalReports', note: 'allTime', icon: FileText, color: 'cyan' }, { key: 'active', label: 'activeReports', note: 'inProgressNote', icon: Clock3, color: 'amber' }, { key: 'critical', label: 'criticalReports', note: 'needsAttention', icon: TriangleAlert, color: 'rose' }, { key: 'resolved', label: 'resolvedReports', note: 'repaired', icon: CircleCheck, color: 'green' }];
</script>
<template>
    <Head :title="t('overview')" /><AppLayout>
        <div class="page-heading"><div><span class="eyebrow">{{ t('region') }} <span class="cyan">/</span> RoadCare</span><h1>{{ government ? t('operations') : `${t('welcome')}, ${page.props.auth.user.name.split(' ')[0]}.` }}</h1><p>{{ t(government ? 'governmentIntro' : 'dashboardIntro') }}</p></div><Link v-if="!government" href="/reports/create" class="button primary"><Plus :size="18" />{{ t('reportDamage') }}</Link><Link v-else href="/government/reports?status=submitted" class="button primary">{{ t('allReports') }}<ArrowUpRight :size="18" /></Link></div>
        <section class="stats-grid"><article v-for="card in cards" :key="card.key" class="stat-card" :class="card.color"><div class="stat-top"><span>{{ t(card.label) }}</span><span class="stat-icon"><component :is="card.icon" :size="20" /></span></div><strong class="stat-number">{{ stats[card.key] }}</strong><span class="stat-note"><i></i>{{ t(card.note) }}</span></article></section>
        <div class="dashboard-middle"><section class="panel map-panel"><div class="panel-heading"><div><h2>{{ t('regionalOverview') }}</h2><p>{{ t('verifiedPins') }}</p></div><Link href="/map" class="text-link">{{ t('openMap') }}<ArrowUpRight :size="16" /></Link></div><WazeMap :reports="mapReports" /></section>
            <section class="panel regional-panel"><div class="panel-heading"><div><h2>{{ t('regionalProgress') }}</h2><p>{{ t('repairsByCity') }}</p></div><span class="mini-icon"><CheckCheck :size="19" /></span></div><div class="city-progress" v-for="city in page.props.options.cities" :key="city"><div><span>{{ t(city) }}</span><strong>{{ cityStats.find(s => s.city === city)?.resolved || 0 }} <span>/ {{ cityStats.find(s => s.city === city)?.total || 0 }}</span></strong></div><div class="progress-track"><span :style="{ width: `${Math.round((cityStats.find(s => s.city === city)?.resolved || 0) / Math.max(cityStats.find(s => s.city === city)?.total || 0, 1) * 100)}%` }"></span></div></div><div class="regional-caption"><ShieldIcon /><p>{{ t('statusHelp') }}</p></div></section>
        </div>
        <section class="panel reports-panel"><div class="panel-heading"><div><h2>{{ t('recentReports') }}</h2></div><Link :href="government ? '/government/reports' : '/reports'" class="text-link">{{ t('viewAll') }}<ArrowUpRight :size="16" /></Link></div><ReportTable :reports="recentReports" /></section>
        <section v-if="!government" class="how-section"><h2>{{ t('howItWorks') }}</h2><div class="how-grid"><article v-for="(step, index) in [{ icon: Camera, title: 'captureTitle', text: 'captureText' }, { icon: MapPin, title: 'locateTitle', text: 'locateText' }, { icon: CheckCheck, title: 'trackTitle', text: 'trackText' }]" :key="step.title"><span class="how-icon"><component :is="step.icon" :size="21" /></span><div><span class="how-step">0{{ index + 1 }}</span><h3>{{ t(step.title) }}</h3><p>{{ t(step.text) }}</p></div></article></div></section>
    </AppLayout>
</template>
