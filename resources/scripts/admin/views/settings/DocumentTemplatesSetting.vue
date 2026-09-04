<template>
  <BaseSettingCard
    :title="$t('settings.document_templates.title')"
    :description="$t('settings.document_templates.description')"
  >
    <form @submit.prevent="saveSettings">
      <BaseInputGrid class="mt-5">
        <BaseInputGroup
          :label="$t('settings.document_templates.allowed_invoices')"
          required
        >
          <BaseMultiselect
            v-model="settings.allowed_invoice_templates"
            mode="tags"
            :options="invoiceTemplates"
            label="name"
            value-prop="name"
            track-by="name"
            :can-deselect="false"
            :content-loading="isLoading"
            class="w-full"
          />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('settings.document_templates.default_invoice')"
          required
        >
          <BaseMultiselect
            v-model="settings.default_invoice_template"
            :options="allowedInvoiceTemplates"
            label="name"
            value-prop="name"
            track-by="name"
            :can-deselect="false"
            :content-loading="isLoading"
            class="w-full"
          />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('settings.document_templates.allowed_estimates')"
          required
        >
          <BaseMultiselect
            v-model="settings.allowed_estimate_templates"
            mode="tags"
            :options="estimateTemplates"
            label="name"
            value-prop="name"
            track-by="name"
            :can-deselect="false"
            :content-loading="isLoading"
            class="w-full"
          />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('settings.document_templates.default_estimate')"
          required
        >
          <BaseMultiselect
            v-model="settings.default_estimate_template"
            :options="allowedEstimateTemplates"
            label="name"
            value-prop="name"
            track-by="name"
            :can-deselect="false"
            :content-loading="isLoading"
            class="w-full"
          />
        </BaseInputGroup>
      </BaseInputGrid>

      <BaseDivider class="my-6" />

      <h3 class="text-base font-semibold text-gray-900">
        {{ $t('settings.document_templates.branding') }}
      </h3>

      <BaseInputGrid class="mt-5">
        <BaseInputGroup :label="$t('settings.document_templates.header_mode')">
          <BaseMultiselect
            v-model="settings.header_mode"
            :options="brandingModes"
            value-prop="value"
            label="label"
            :can-deselect="false"
          />
        </BaseInputGroup>

        <BaseInputGroup
          v-if="settings.header_mode === 'image'"
          :label="$t('settings.document_templates.header_image')"
        >
          <BaseFileUploader
            v-model="headerFiles"
            base64
            accept="image/png,image/jpeg,image/gif,image/webp"
            @change="onHeaderChange"
            @remove="removeHeader"
          />
        </BaseInputGroup>

        <BaseInputGroup
          v-if="settings.header_mode === 'html'"
          :label="$t('settings.document_templates.header_html')"
        >
          <BaseTextarea v-model="settings.header_html" rows="5" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.document_templates.footer_mode')">
          <BaseMultiselect
            v-model="settings.footer_mode"
            :options="brandingModes"
            value-prop="value"
            label="label"
            :can-deselect="false"
          />
        </BaseInputGroup>

        <BaseInputGroup
          v-if="settings.footer_mode === 'image'"
          :label="$t('settings.document_templates.footer_image')"
        >
          <BaseFileUploader
            v-model="footerFiles"
            base64
            accept="image/png,image/jpeg,image/gif,image/webp"
            @change="onFooterChange"
            @remove="removeFooter"
          />
        </BaseInputGroup>

        <BaseInputGroup
          v-if="settings.footer_mode === 'html'"
          :label="$t('settings.document_templates.footer_html')"
        >
          <BaseTextarea v-model="settings.footer_html" rows="5" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.document_templates.watermark')">
          <BaseFileUploader
            v-model="watermarkFiles"
            base64
            accept="image/png,image/jpeg,image/gif,image/webp"
            @change="onWatermarkChange"
            @remove="removeWatermark"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('settings.document_templates.paid_stamp')">
          <BaseFileUploader
            v-model="paidStampFiles"
            base64
            accept="image/png,image/jpeg,image/gif,image/webp"
            @change="onPaidStampChange"
            @remove="removePaidStamp"
          />
        </BaseInputGroup>
      </BaseInputGrid>

      <BaseButton
        type="submit"
        :loading="isSaving"
        :disabled="isSaving || !canSave"
        class="mt-6"
      >
        <template #left="slotProps">
          <BaseIcon
            v-if="!isSaving"
            name="ArrowDownOnSquareIcon"
            :class="slotProps.class"
          />
        </template>
        {{ $t('general.save') }}
      </BaseButton>
    </form>
  </BaseSettingCard>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import http from '@/scripts/http'
import { handleError } from '@/scripts/helpers/error-handling'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const invoiceTemplates = ref([])
const estimateTemplates = ref([])
const isLoading = ref(true)
const isSaving = ref(false)
const headerFiles = ref([])
const footerFiles = ref([])
const watermarkFiles = ref([])
const paidStampFiles = ref([])
const settings = reactive({
  allowed_invoice_templates: [],
  default_invoice_template: null,
  allowed_estimate_templates: [],
  default_estimate_template: null,
  header_mode: 'none',
  header_html: '',
  footer_mode: 'none',
  footer_html: '',
})
const brandingModes = computed(() => [
  { value: 'none', label: t('settings.document_templates.mode_none') },
  { value: 'image', label: t('settings.document_templates.mode_image') },
  { value: 'html', label: t('settings.document_templates.mode_html') },
])

const allowedInvoiceTemplates = computed(() =>
  invoiceTemplates.value.filter((template) =>
    settings.allowed_invoice_templates.includes(template.name),
  ),
)
const allowedEstimateTemplates = computed(() =>
  estimateTemplates.value.filter((template) =>
    settings.allowed_estimate_templates.includes(template.name),
  ),
)
const canSave = computed(
  () =>
    settings.allowed_invoice_templates.length > 0 &&
    settings.allowed_estimate_templates.length > 0 &&
    settings.default_invoice_template &&
    settings.default_estimate_template,
)

watch(
  () => settings.allowed_invoice_templates,
  (allowed) => {
    if (!allowed.includes(settings.default_invoice_template)) {
      settings.default_invoice_template = allowed[0] || null
    }
  },
  { deep: true },
)

watch(
  () => settings.allowed_estimate_templates,
  (allowed) => {
    if (!allowed.includes(settings.default_estimate_template)) {
      settings.default_estimate_template = allowed[0] || null
    }
  },
  { deep: true },
)

onMounted(loadSettings)

async function loadSettings() {
  isLoading.value = true

  try {
    const response = await http.get('/api/v1/company/document-templates')
    invoiceTemplates.value = response.data.invoice_templates
    estimateTemplates.value = response.data.estimate_templates
    Object.assign(settings, response.data.settings)
    headerFiles.value = response.data.settings.header_url
      ? [{ image: response.data.settings.header_url }]
      : []
    footerFiles.value = response.data.settings.footer_url
      ? [{ image: response.data.settings.footer_url }]
      : []
    watermarkFiles.value = response.data.settings.watermark_url
      ? [{ image: response.data.settings.watermark_url }]
      : []
    paidStampFiles.value = response.data.settings.paid_stamp_url
      ? [{ image: response.data.settings.paid_stamp_url }]
      : []
  } catch (error) {
    handleError(error)
  } finally {
    isLoading.value = false
  }
}

async function uploadAsset(asset, file, fileList) {
  await http.post(`/api/v1/company/document-branding/${asset}`, {
    asset_data: JSON.stringify({ name: fileList.name, data: file }),
  })
}

async function removeAsset(asset) {
  await http.post(`/api/v1/company/document-branding/${asset}`, {
    remove: true,
  })
}

function onHeaderChange(name, file, count, fileList) {
  return uploadAsset('header', file, fileList)
}

function onFooterChange(name, file, count, fileList) {
  return uploadAsset('footer', file, fileList)
}

function onWatermarkChange(name, file, count, fileList) {
  return uploadAsset('watermark', file, fileList)
}

function onPaidStampChange(name, file, count, fileList) {
  return uploadAsset('paid_stamp', file, fileList)
}

function removeHeader() {
  return removeAsset('header')
}

function removeFooter() {
  return removeAsset('footer')
}

function removeWatermark() {
  return removeAsset('watermark')
}

function removePaidStamp() {
  return removeAsset('paid_stamp')
}

async function saveSettings() {
  isSaving.value = true

  try {
    const response = await http.put(
      '/api/v1/company/document-templates',
      settings,
    )
    Object.assign(settings, response.data.settings)
    notificationStore.showNotification({
      type: 'success',
      message: t('settings.document_templates.saved'),
    })
  } catch (error) {
    handleError(error)
  } finally {
    isSaving.value = false
  }
}
</script>
