<script setup>
import { router } from '@inertiajs/vue3';
import { Languages } from '@lucide/vue';
import { useI18n } from '../i18n';
const { locale } = useI18n();
function change() { router.post('/language', { locale: locale.value === 'en' ? 'ckb' : 'en' }, { preserveScroll: true }); }
</script>
<template><button type="button" class="language-switch" @click="change" :aria-label="locale === 'en' ? 'Switch to Kurdish Sorani' : 'Switch to English'"><Languages :size="17" /><span>{{ locale === 'en' ? 'کوردی' : 'English' }}</span></button></template>
