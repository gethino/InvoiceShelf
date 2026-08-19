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
import { computed, onMounted, reactive, ref } from 'vue'
import http from '@/scripts/http'

const t = (...parameters) => window.i18n.global.t(...parameters)
const form = reactive({
  brand_color: '#4a3dff',
  taxes_enabled: false,
  use_on_login: false,
})
const isLoading = ref(true)
const isSaving = ref(false)
const logoData = ref(null)
const logoRemoved = ref(false)
const previewLogo = ref([])
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
  } catch {
    showError()
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

async function save() {
  isSaving.value = true
  message.value = ''

  try {
    await http.put('/api/v1/tripoli-customizations/settings', form)

    if (logoData.value || logoRemoved.value) {
      const payload = new FormData()

      if (logoData.value) {
        payload.append('company_logo', JSON.stringify(logoData.value))
      }

      payload.append('is_company_logo_removed', logoRemoved.value)
      await http.post('/api/v1/company/upload-logo', payload)
    }

    window.TripoliCustomizations.applyBrandColor(form.brand_color)
    hasError.value = false
    message.value = t('tripoli_customizations.settings.saved')
    window.setTimeout(() => window.location.reload(), 500)
  } catch {
    showError()
  } finally {
    isSaving.value = false
  }
}

function showError() {
  hasError.value = true
  message.value = t('tripoli_customizations.common.error')
}
</script>
