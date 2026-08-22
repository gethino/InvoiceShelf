<template>
  <div class="space-y-6">
    <BaseSettingCard
      :title="$t('tripoli_customizations.settings.title')"
      :description="$t('tripoli_customizations.settings.description')"
    >
      <p v-if="isLoading" class="text-sm text-gray-500">
        {{ $t('tripoli_customizations.common.loading') }}
      </p>

      <form v-else class="space-y-6" @submit.prevent="save">
        <BaseInputGrid>
          <BaseInputGroup :label="$t('tripoli_customizations.settings.logo')">
            <BaseFileUploader
              v-model="previewLogo"
              base64
              @change="onLogoChange"
              @remove="onLogoRemove"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('tripoli_customizations.settings.dark_logo')"
          >
            <BaseFileUploader
              v-model="previewDarkLogo"
              base64
              accept="image/*"
              @change="onDarkLogoChange"
              @remove="onDarkLogoRemove"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('tripoli_customizations.settings.favicon')"
          >
            <BaseFileUploader
              v-model="previewFavicon"
              base64
              accept="image/png"
              @change="onFaviconChange"
              @remove="onFaviconRemove"
            />
            <p class="mt-2 text-xs text-gray-500">
              {{ $t('tripoli_customizations.settings.favicon_help') }}
            </p>
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('tripoli_customizations.settings.brand_color')"
          >
            <div class="flex items-center gap-3">
              <input
                v-model="form.brand_color"
                type="color"
                class="h-11 w-16 cursor-pointer rounded border border-gray-300 bg-white p-1"
              />
              <BaseInput v-model="form.brand_color" maxlength="7" />
            </div>
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('tripoli_customizations.settings.theme_color')"
          >
            <div class="flex items-center gap-3">
              <input
                v-model="form.theme_color"
                type="color"
                class="h-11 w-16 cursor-pointer rounded border border-gray-300 bg-white p-1"
              />
              <BaseInput v-model="form.theme_color" maxlength="7" />
            </div>
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('tripoli_customizations.settings.meta_title')"
          >
            <BaseInput v-model="form.meta_title" maxlength="255" />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('tripoli_customizations.settings.meta_description')"
          >
            <BaseTextarea
              v-model="form.meta_description"
              rows="3"
              maxlength="255"
            />
          </BaseInputGroup>
        </BaseInputGrid>

        <BaseCheckbox
          v-model="form.use_on_login"
          :disabled="form.use_on_login"
          :label="$t('tripoli_customizations.settings.login_default')"
          :description="
            $t('tripoli_customizations.settings.login_default_help')
          "
        />

        <div
          class="flex items-start justify-between gap-6 rounded-lg border border-gray-200 p-4"
        >
          <div>
            <p class="font-medium text-gray-900">
              {{ $t('tripoli_customizations.settings.taxes') }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('tripoli_customizations.settings.taxes_help') }}
            </p>
          </div>
          <BaseSwitch v-model="form.taxes_enabled" />
        </div>

        <div
          class="flex items-start justify-between gap-6 rounded-lg border border-gray-200 p-4"
        >
          <div>
            <p class="font-medium text-gray-900">
              {{ $t('tripoli_customizations.settings.simplified_login') }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('tripoli_customizations.settings.simplified_login_help') }}
            </p>
          </div>
          <BaseSwitch v-model="form.simplified_login" />
        </div>

        <div
          class="flex items-start justify-between gap-6 rounded-lg border border-gray-200 p-4"
        >
          <div>
            <p class="font-medium text-gray-900">
              {{ $t('tripoli_customizations.settings.quick_login') }}
            </p>
            <p class="mt-1 text-sm text-gray-500">
              {{ $t('tripoli_customizations.settings.quick_login_help') }}
            </p>
          </div>
          <BaseSwitch v-model="form.quick_login_enabled" />
        </div>

        <p v-if="message" :class="messageClass" class="text-sm">
          {{ message }}
        </p>

        <BaseButton type="submit" :loading="isSaving" :disabled="isSaving">
          {{ $t('tripoli_customizations.settings.save') }}
        </BaseButton>
      </form>
    </BaseSettingCard>
  </div>
</template>

<script setup>
import http from '@/scripts/http'
import { computed, onMounted, reactive, ref } from 'vue'
import { requestErrorMessage } from '../branding.js'

const t = (...parameters) => window.i18n.global.t(...parameters)
const form = reactive({
  brand_color: '#4a3dff',
  meta_title: '',
  meta_description: '',
  theme_color: '#ffffff',
  taxes_enabled: false,
  use_on_login: false,
  simplified_login: true,
  quick_login_enabled: true,
})
const isLoading = ref(true)
const isSaving = ref(false)
const logoData = ref(null)
const logoRemoved = ref(false)
const previewLogo = ref([])
const darkLogoData = ref(null)
const darkLogoRemoved = ref(false)
const previewDarkLogo = ref([])
const faviconData = ref(null)
const faviconRemoved = ref(false)
const previewFavicon = ref([])
const message = ref('')
const hasError = ref(false)
const messageClass = computed(() =>
  hasError.value ? 'text-red-600' : 'text-green-600',
)

onMounted(load)

async function load() {
  isLoading.value = true

  try {
    const { data } = await http.get('/api/v1/tripoli-customizations/settings')
    Object.assign(form, data)
    previewLogo.value = data.logo_url ? [{ image: data.logo_url }] : []
    previewDarkLogo.value = data.dark_logo_url
      ? [{ image: data.dark_logo_url }]
      : []
    previewFavicon.value = data.favicon_url ? [{ image: data.favicon_url }] : []
  } catch (error) {
    showError(error)
  } finally {
    isLoading.value = false
  }
}

function onLogoChange(fileName, file, fileCount, fileList) {
  logoData.value = {
    name: fileList.name || fileName,
    data: file,
  }
  logoRemoved.value = false
}

function onLogoRemove() {
  logoData.value = null
  logoRemoved.value = true
}

function onDarkLogoChange(fileName, file, fileCount, fileList) {
  darkLogoData.value = {
    name: fileList.name || fileName,
    data: file,
  }
  darkLogoRemoved.value = false
}

function onDarkLogoRemove() {
  darkLogoData.value = null
  darkLogoRemoved.value = true
}

function onFaviconChange(fileName, file, fileCount, fileList) {
  faviconData.value = {
    name: fileList.name || fileName,
    data: file,
  }
  faviconRemoved.value = false
}

function onFaviconRemove() {
  faviconData.value = null
  faviconRemoved.value = true
}

async function save() {
  isSaving.value = true
  message.value = ''

  try {
    await http.put('/api/v1/tripoli-customizations/settings', form)

    if (
      logoData.value ||
      logoRemoved.value ||
      darkLogoData.value ||
      darkLogoRemoved.value ||
      faviconData.value ||
      faviconRemoved.value
    ) {
      const payload = new FormData()

      if (logoData.value) {
        payload.append('company_logo', JSON.stringify(logoData.value))
      }

      payload.append('is_company_logo_removed', logoRemoved.value)
      if (darkLogoData.value) {
        payload.append('dark_company_logo', JSON.stringify(darkLogoData.value))
      }
      payload.append('is_dark_company_logo_removed', darkLogoRemoved.value)
      if (faviconData.value) {
        payload.append('company_favicon', JSON.stringify(faviconData.value))
      }
      payload.append('is_company_favicon_removed', faviconRemoved.value)
      await http.post('/api/v1/company/upload-logo', payload)
    }

    window.TripoliCustomizations.applyBrandColor(form.brand_color)
    hasError.value = false
    message.value = t('tripoli_customizations.settings.saved')
    window.setTimeout(() => window.location.reload(), 500)
  } catch (error) {
    showError(error)
  } finally {
    isSaving.value = false
  }
}

function showError(error = null) {
  hasError.value = true
  message.value = requestErrorMessage(
    error,
    t('tripoli_customizations.common.error'),
  )
}
</script>
