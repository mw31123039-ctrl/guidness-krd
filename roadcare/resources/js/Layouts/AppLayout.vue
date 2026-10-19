<script setup>
import { computed, ref, watchEffect } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Map, ClipboardList, Plus, LogOut, ShieldCheck, Menu, X, ArrowUpRight, MapPin } from '@lucide/vue';
import LanguageSwitch from '../Components/LanguageSwitch.vue';
import { useI18n } from '../i18n';
const page = usePage();
const { t, locale } = useI18n();
const mobileOpen = ref(false);
const user = computed(() => page.props.auth.user);
const government = computed(() => user.value.role === 'government');
const nav = computed(() => [
    { label: 'overview', href: government.value ? '/government' : '/dashboard', icon: LayoutDashboard },
    { label: government.value ? 'allReports' : 'myReports', href: government.value ? '/government/reports' : '/reports', icon: ClipboardList },
    { label: 'roadMap', href: '/map', icon: Map },
]);
const active = href => page.url.split('?')[0] === href;
watchEffect(() => { document.documentElement.lang = locale.value; document.documentElement.dir = locale.value === 'ckb' ? 'rtl' : 'ltr'; });
</script>
<template>
    <div class="app-shell" :dir="locale === 'ckb' ? 'rtl' : 'ltr'">
        <button v-if="mobileOpen" class="sidebar-scrim" @click="mobileOpen = false" aria-label="Close navigation"></button>
        <aside class="sidebar" :class="{ 'is-open': mobileOpen }">
            <Link class="brand" :href="government ? '/government' : '/dashboard'"><img src="/favicon.svg" alt="" /><span>Road<span class="cyan">Care</span><small>{{ t('platform') }}</small></span></Link>
            <div class="workspace-label"><ShieldCheck v-if="government" :size="15" /><span v-else class="live-dot"></span>{{ t(government ? 'governmentPortal' : 'citizenPortal') }}</div>
            <nav class="navigation"><Link v-for="item in nav" :key="item.href" :href="item.href" :class="{ active: active(item.href) }" @click="mobileOpen = false"><component :is="item.icon" :size="19" /><span>{{ t(item.label) }}</span><span v-if="active(item.href)" class="nav-active-dot"></span></Link></nav>
            <Link v-if="!government" href="/reports/create" class="button primary sidebar-report"><Plus :size="19" />{{ t('reportDamage') }}</Link>
            <div class="sidebar-bottom"><div class="community-card"><div class="community-icon"><MapPin :size="23" /></div><strong>{{ t('supportTitle') }}</strong><p>{{ t('supportText') }}</p><span>{{ t('region') }} <ArrowUpRight :size="14" /></span></div>
                <Link class="logout" href="/logout" method="post" as="button"><LogOut :size="18" />{{ t('signOut') }}</Link>
                <div class="sidebar-foot">RoadCare <span>© {{ new Date().getFullYear() }}</span></div>
            </div>
        </aside>
        <div class="main-shell">
            <header class="topbar"><div class="topbar-region"><button class="icon-button mobile-toggle" @click="mobileOpen = !mobileOpen" aria-label="Toggle navigation"><Menu :size="21" /></button><MapPin :size="16" /><span>{{ t('region') }}</span><span class="topbar-divider"></span><span class="topbar-small">{{ t(government ? 'governmentPortal' : 'citizenPortal') }}</span></div><div class="topbar-actions"><LanguageSwitch /><span class="profile-divider"></span><div class="profile-avatar">{{ user.name.charAt(0).toUpperCase() }}</div><div class="profile-info"><strong>{{ user.name }}</strong><span>{{ t(user.role) }}</span></div></div></header>
            <main class="main-content"><div v-if="page.props.flash.success" class="flash" role="status"><ShieldCheck :size="19" />{{ page.props.flash.success }}</div><div v-if="page.props.isDemo" class="demo-notice">{{ t('demoNotice') }}</div><slot /></main>
            <footer class="main-footer"><span>RoadCare <span class="footer-dot">·</span> {{ t('region') }}</span><span>{{ t('supportTitle') }}</span></footer>
        </div>
    </div>
</template>
