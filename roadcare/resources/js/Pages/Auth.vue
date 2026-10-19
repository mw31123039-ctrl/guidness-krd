<script setup>
import { computed, ref, watchEffect } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowRight, Eye, EyeOff, Camera, MapPin, ShieldCheck, Check, Route } from '@lucide/vue';
import LanguageSwitch from '../Components/LanguageSwitch.vue';
import AuthRegionMap from '../Components/AuthRegionMap.vue';
import { useI18n } from '../i18n';
const props = defineProps({ mode: String });
const page = usePage();
const { t, locale } = useI18n();
const register = computed(() => props.mode === 'register');
const visible = ref(false);
const form = useForm({ name: '', email: '', password: '', password_confirmation: '', phone: '', city: 'Erbil', remember: false });
const submit = () => form.post(register.value ? '/register' : '/login', { onFinish: () => form.reset('password', 'password_confirmation') });
function fillDemo(role) { form.email = role === 'government' ? 'government@roadcare.test' : 'citizen@demo.com'; form.password = 'RoadCare123!'; }
watchEffect(() => { document.documentElement.dir = locale.value === 'ckb' ? 'rtl' : 'ltr'; document.documentElement.lang = locale.value; });
</script>
<template>
    <Head :title="t(register ? 'createAccount' : 'signIn')" />
    <div class="auth-page" :dir="locale === 'ckb' ? 'rtl' : 'ltr'">
        <section class="auth-story"><Link href="/" class="brand"><img src="/favicon.svg" alt="" /><span>Road<span class="cyan">Care</span><small>{{ t('region') }}</small></span></Link>
            <div class="auth-story-content"><span class="eyebrow">{{ t('authEyebrow') }}</span><h1>{{ t('authHeading') }}</h1><p>{{ t('authText') }}</p>
                <div class="auth-route-art auth-kurdistan-art"><AuthRegionMap /></div>
                <ul class="auth-features"><li><Camera :size="18" />{{ t('authFeature1') }}</li><li><MapPin :size="18" />{{ t('authFeature2') }}</li><li><ShieldCheck :size="18" />{{ t('authFeature3') }}</li></ul>
            </div><div class="auth-coverage"><span class="live-dot"></span>{{ t('coverage') }}</div>
        </section>
        <section class="auth-form-side"><div class="auth-language"><LanguageSwitch /></div><div class="auth-form-inner"><div class="auth-form-icon"><Route :size="25" /></div><span class="eyebrow muted">{{ t('citizenPortal') }} <span class="cyan">/</span> {{ t('government') }}</span><h2>{{ t(register ? 'registerTitle' : 'loginTitle') }}</h2><p class="muted">{{ t(register ? 'registerSubtitle' : 'loginSubtitle') }}</p>
            <form @submit.prevent="submit" class="auth-form" novalidate>
                <label v-if="register" class="field">{{ t('fullName') }}<input v-model="form.name" autocomplete="name" maxlength="100" :class="{ invalid: form.errors.name }" /><span v-if="form.errors.name" class="field-error">{{ form.errors.name }}</span></label>
                <label class="field">{{ t('email') }}<input v-model="form.email" type="email" autocomplete="email" placeholder="you@example.com" dir="ltr" :class="{ invalid: form.errors.email }" /><span v-if="form.errors.email" class="field-error" role="alert">{{ form.errors.email }}</span></label>
                <label class="field">{{ t('password') }}<span class="password-field"><input v-model="form.password" :type="visible ? 'text' : 'password'" :autocomplete="register ? 'new-password' : 'current-password'" :class="{ invalid: form.errors.password }" /><button type="button" @click="visible = !visible" :aria-label="visible ? 'Hide password' : 'Show password'"><EyeOff v-if="visible" :size="18" /><Eye v-else :size="18" /></button></span><span v-if="form.errors.password" class="field-error">{{ form.errors.password }}</span><small v-if="register" class="field-hint">{{ t('passwordHint') }}</small></label>
                <label v-if="register" class="field">{{ t('confirmPassword') }}<input v-model="form.password_confirmation" type="password" autocomplete="new-password" /></label>
                <div v-if="register" class="form-grid"><label class="field">{{ t('city') }}<select v-model="form.city"><option v-for="city in page.props.options.cities" :key="city" :value="city">{{ t(city) }}</option></select><span class="field-error" v-if="form.errors.city">{{ form.errors.city }}</span></label><label class="field">{{ t('phone') }}<input v-model="form.phone" type="tel" autocomplete="tel" dir="ltr" /><span class="field-error" v-if="form.errors.phone">{{ form.errors.phone }}</span></label></div>
                <label v-if="!register" class="checkbox-label"><input v-model="form.remember" type="checkbox" />{{ t('remember') }}</label>
                <button class="button primary auth-submit" :disabled="form.processing"><span>{{ t(form.processing ? (register ? 'creatingAccount' : 'signingIn') : (register ? 'createAccount' : 'signIn')) }}</span><ArrowRight :size="18" /></button>
            </form><p class="auth-switch">{{ t(register ? 'haveAccount' : 'noAccount') }} <Link :href="register ? '/login' : '/register'">{{ t(register ? 'signIn' : 'createAccount') }}</Link></p>
            <div v-if="!register && page.props.isDemo" class="demo-accounts"><span>{{ t('localDemo') }}</span><div><button @click="fillDemo('citizen')">{{ t('demoCitizen') }}</button><button @click="fillDemo('government')">{{ t('demoGovernment') }}</button></div><small>{{ t('demoHint') }}</small></div><p class="auth-government-note"><ShieldCheck :size="14" />{{ t('governmentAccess') }}</p>
        </div><div class="auth-copyright">RoadCare © {{ new Date().getFullYear() }} <span>{{ t('region') }}</span></div></section>
    </div>
</template>
