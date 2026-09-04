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
const settings = reactive({
  allowed_invoice_templates: [],
  default_invoice_template: null,
  allowed_estimate_templates: [],
  default_estimate_template: null,
})

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
  } catch (error) {
    handleError(error)
  } finally {
    isLoading.value = false
  }
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
