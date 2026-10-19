<script setup>
import { computed, onUnmounted, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Camera, MapPin, Navigation, Send, ShieldCheck, X, TriangleAlert } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import WazeMap from '../../Components/WazeMap.vue';
import { useI18n } from '../../i18n';
const page = usePage(); const { t } = useI18n();
const input = ref(null), preview = ref(''), photoError = ref(''), gpsError = ref(''), locating = ref(false);
const form = useForm({ title: '', category: 'pothole', severity: 'moderate', description: '', city: page.props.auth.user.city || 'Erbil', address: '', latitude: '', longitude: '', photo: null });
const position = computed(() => form.latitude !== '' && form.longitude !== '' ? { lat: form.latitude, lng: form.longitude } : null);
function pickPhoto(event) {
    const file = event.target.files?.[0]; photoError.value = '';
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 8 * 1024 * 1024) { photoError.value = t('uploadFormats'); event.target.value = ''; return; }
    if (preview.value) URL.revokeObjectURL(preview.value);
    form.photo = file; preview.value = URL.createObjectURL(file); form.clearErrors('photo');
}
function removePhoto() { if (preview.value) URL.revokeObjectURL(preview.value); preview.value = ''; form.photo = null; if (input.value) input.value.value = ''; }
function selectLocation(location) { form.latitude = location.lat; form.longitude = location.lng; form.clearErrors('latitude', 'longitude'); }
function gps() {
    gpsError.value = '';
    if (!navigator.geolocation) { gpsError.value = t('gpsFailed'); return; }
    locating.value = true;
    navigator.geolocation.getCurrentPosition(position => { selectLocation({ lat: Number(position.coords.latitude.toFixed(7)), lng: Number(position.coords.longitude.toFixed(7)) }); locating.value = false; }, () => { locating.value = false; gpsError.value = t('gpsFailed'); }, { enableHighAccuracy: true, timeout: 15000 });
}
function submit() { form.post('/reports', { forceFormData: true, onError: () => document.querySelector('.field-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' }) }); }
onUnmounted(() => { if (preview.value) URL.revokeObjectURL(preview.value); });
</script>
<template>
    <Head :title="t('reportDamage')" /><AppLayout>
        <div class="page-heading"><div><span class="eyebrow">{{ t('citizenPortal') }} <span class="cyan">/</span> {{ t('reportDamage') }}</span><h1>{{ t('reportTitle') }}</h1><p>{{ t('reportSubtitle') }}</p></div></div>
        <form @submit.prevent="submit" novalidate><div v-if="Object.keys(form.errors).length" class="form-error-banner" role="alert"><TriangleAlert :size="18" />{{ t('errorTitle') }}</div>
            <div class="create-grid"><div class="create-main"><section class="panel form-panel"><div class="section-number">01</div><h2>{{ t('photo') }} <span class="required-star">*</span></h2><input ref="input" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" id="report-photo" @change="pickPhoto" />
                    <div v-if="preview" class="photo-preview"><img :src="preview" :alt="t('photo')" /><div><button type="button" @click="input.click()" class="button secondary">{{ t('changePhoto') }}</button><button type="button" @click="removePhoto" class="icon-button" :aria-label="t('remove')"><X :size="18" /></button></div></div>
                    <label v-else for="report-photo" class="upload-area" tabindex="0" @keydown.enter.prevent="input.click()" @keydown.space.prevent="input.click()"><span class="upload-camera"><Camera :size="29" /></span><strong>{{ t('uploadTitle') }}</strong><span>{{ t('uploadHint') }}</span><small>{{ t('uploadFormats') }}</small></label>
                    <span v-if="form.errors.photo || photoError" class="field-error">{{ form.errors.photo || photoError }}</span><p class="field-hint photo-hint">{{ t('photoHelp') }}</p></section>
                <section class="panel form-panel"><div class="section-number">02</div><h2>{{ t('details') }}</h2><label class="field">{{ t('title') }} *<input v-model="form.title" :placeholder="t('titlePlaceholder')" maxlength="140" :class="{ invalid: form.errors.title }" /><span v-if="form.errors.title" class="field-error">{{ form.errors.title }}</span></label>
                    <label class="field">{{ t('category') }} *<select v-model="form.category"><option v-for="category in page.props.options.categories" :key="category" :value="category">{{ t(category) }}</option></select><span v-if="form.errors.category" class="field-error">{{ form.errors.category }}</span></label>
                    <fieldset class="severity-fieldset"><legend>{{ t('severity') }} *</legend><div class="severity-options"><label v-for="severity in page.props.options.severities" :key="severity" :class="[severity, { selected: form.severity === severity }]"><input type="radio" v-model="form.severity" :value="severity" name="severity" /><i></i>{{ t(severity) }}</label></div><span v-if="form.errors.severity" class="field-error">{{ form.errors.severity }}</span></fieldset>
                    <label class="field">{{ t('description') }} *<textarea v-model="form.description" :placeholder="t('descriptionPlaceholder')" rows="5" maxlength="3000" :class="{ invalid: form.errors.description }"></textarea><span v-if="form.errors.description" class="field-error">{{ form.errors.description }}</span></label>
                </section></div>
                <div class="create-side"><section class="panel form-panel location-panel"><div class="section-number">03</div><h2>{{ t('location') }}</h2><label class="field">{{ t('city') }} *<select v-model="form.city"><option v-for="city in page.props.options.cities" :key="city" :value="city">{{ t(city) }}</option></select><span v-if="form.errors.city" class="field-error">{{ form.errors.city }}</span></label><label class="field">{{ t('address') }} *<input v-model="form.address" :placeholder="t('addressPlaceholder')" maxlength="255" :class="{ invalid: form.errors.address }" /><span v-if="form.errors.address" class="field-error">{{ form.errors.address }}</span></label>
                    <WazeMap pickable :position="position" @select="selectLocation" /><button type="button" class="button secondary gps-button" @click="gps" :disabled="locating"><Navigation :size="17" />{{ t(locating ? 'locating' : 'useGps') }}</button><span v-if="gpsError" class="field-error" role="alert">{{ gpsError }}</span>
                    <div class="form-grid"><label class="field">{{ t('latitude') }} *<input v-model="form.latitude" type="number" step="any" inputmode="decimal" placeholder="36.1911" dir="ltr" /></label><label class="field">{{ t('longitude') }} *<input v-model="form.longitude" type="number" step="any" inputmode="decimal" placeholder="44.0092" dir="ltr" /></label></div><span v-if="form.errors.latitude || form.errors.longitude" class="field-error">{{ form.errors.latitude || form.errors.longitude }}</span><p class="field-hint">{{ t('locationHint') }}</p>
                </section><section class="privacy-card"><ShieldCheck :size="22" /><div><strong>{{ t('reportSummary') }}</strong><p>{{ t('reportPrivacy') }}</p><p>{{ t('statusHelp') }}</p></div></section></div>
            </div><div class="form-actions"><Link href="/reports" class="button secondary">{{ t('cancel') }}</Link><div v-if="form.progress" class="upload-progress">{{ Math.round(form.progress.percentage) }}%</div><button type="submit" class="button primary" :disabled="form.processing"><Send :size="17" />{{ t(form.processing ? 'submitting' : 'submitReport') }}</button></div>
        </form>
    </AppLayout>
</template>
