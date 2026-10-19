<script setup>
import { useI18n } from '../i18n';
defineProps({ value: String, severity: Boolean });
const { t } = useI18n();
</script>
<template><span class="badge" :class="[value, { 'severity-badge': severity }]"><i></i>{{ t(value) }}</span></template>
