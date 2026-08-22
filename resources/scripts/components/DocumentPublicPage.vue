<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DocumentPreviewFrame from '@/scripts/components/DocumentPreviewFrame.vue'
import http from '@/scripts/http'

const props = defineProps({
  documentType: {
    type: String,
    required: true,
  },
})

const documentTypes = {
  invoice: {
    endpoint: 'invoices',
    numberField: 'invoice_number',
  },
  estimate: {
    endpoint: 'estimates',
    numberField: 'estimate_number',
  },
  payment: {
    endpoint: 'payments',
    numberField: 'payment_number',
  },
}

const documentData = ref(null)
const route = useRoute()
const router = useRouter()

const documentConfig = computed(() => documentTypes[props.documentType])
const pageTitle = computed(
  () => documentData.value?.[documentConfig.value.numberField] ?? '',
)
const previewLink = computed(() => `${route.path}?preview=1`)
const pdfLink = computed(() => `${route.path}?pdf=1`)
const customerLogo = computed(() => window.customer_logo || false)
const canPayInvoice = computed(
  () =>
    props.documentType === 'invoice' &&
    documentData.value?.paid_status !== 'PAID' &&
    documentData.value?.payment_module_enabled,
)

watch(
  () => [props.documentType, route.params.hash],
  () => loadDocument(),
  { immediate: true },
)

async function loadDocument() {
  const response = await http.get(
    `/customer/${documentConfig.value.endpoint}/${route.params.hash}`,
  )

  documentData.value = response.data.data
}

function getLogo() {
  return new URL('$images/logo-gray.png', import.meta.url)
}

function payInvoice() {
  router.push({
    name: 'invoice.pay',
    params: {
      hash: route.params.hash,
      company: documentData.value.company.slug,
    },
  })
}
</script>

<template>
  <div class="h-screen min-h-0 overflow-y-auto">
    <div class="h-5 bg-linear-to-r from-primary-500 to-primary-400"></div>

    <div class="relative mx-auto w-full max-w-6xl px-4 py-6 pb-20 md:px-6">
      <BasePageHeader :title="pageTitle">
        <template #actions>
          <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
            <BaseButton
              tag="a"
              :href="pdfLink"
              target="_blank"
              rel="noopener noreferrer"
              variant="primary-outline"
              class="w-full justify-center sm:w-auto"
            >
              {{ $t('general.view_pdf') }}
            </BaseButton>

            <BaseButton
              v-if="canPayInvoice"
              variant="primary"
              class="w-full justify-center sm:w-auto"
              @click="payInvoice"
            >
              {{ $t('general.pay_invoice') }}
            </BaseButton>
          </div>
        </template>
      </BasePageHeader>

      <DocumentPreviewFrame
        :src="previewLink"
        :title="pageTitle || $t('general.preview')"
        class="mt-8"
      />

      <div
        v-if="!customerLogo"
        class="mt-4 flex items-center justify-center font-normal text-gray-500"
      >
        Powered by
        <a
          href="https://invoiceshelf.com"
          target="_blank"
          rel="noopener noreferrer"
        >
          <img :src="getLogo()" class="mb-1 ms-1 h-4" alt="InvoiceShelf" />
        </a>
      </div>
    </div>
  </div>
</template>
