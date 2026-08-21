<template>
  <BaseContentPlaceholders v-if="contentLoading">
    <BaseContentPlaceholdersBox
      :rounded="true"
      class="w-full"
      style="height: 38px"
    />
  </BaseContentPlaceholders>
  <div v-else class="relative w-full">
    <span
      v-if="currencyPresentation.symbol"
      aria-hidden="true"
      :dir="currencyPresentation.symbolAfterAmount ? 'ltr' : 'rtl'"
      :class="[
        'pointer-events-none absolute inset-y-0 z-10 flex items-center text-sm text-gray-500',
        isCurrencySymbolOnRight ? 'right-3' : 'left-3',
      ]"
    >
      {{ currencyPresentation.symbol }}
    </span>

    <input
      v-bind="$attrs"
      :value="inputValue"
      type="text"
      inputmode="decimal"
      dir="ltr"
      :class="[
        inputClass,
        invalidClass,
        isCurrencySymbolOnRight ? 'pr-14' : 'pl-14',
        isArabic ? 'text-right' : 'text-left',
      ]"
      :disabled="disabled"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import {
  formatMoneyInputDisplay,
  getCurrencyPresentation,
  isArabicLocale,
  isCurrencySymbolOnRight as currencySymbolIsOnRight,
  normalizeMoneyInput,
  parseMoneyInput,
} from '@/scripts/helpers/currency-format'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: [String, Number],
    required: true,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  inputClass: {
    type: String,
    default:
      'font-base block w-full sm:text-sm border-gray-200 rounded-md text-black',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  percent: {
    type: Boolean,
    default: false,
  },
  currency: {
    type: Object,
    default: null,
  },
})
const emit = defineEmits(['update:modelValue', 'input', 'focus', 'blur'])
const companyStore = useCompanyStore()
const { locale } = useI18n()
const inputValue = ref('')
const isFocused = ref(false)

const selectedCurrency = computed(() => {
  return props.currency || companyStore.selectedCompanyCurrency
})

const currencyPresentation = computed(() => {
  return getCurrencyPresentation(selectedCurrency.value, locale.value)
})

const isArabic = computed(() => isArabicLocale(locale.value))
const isCurrencySymbolOnRight = computed(() => {
  return currencySymbolIsOnRight(currencyPresentation.value, locale.value)
})

watch(
  [() => props.modelValue, selectedCurrency],
  ([modelValue, currency]) => {
    if (!isFocused.value && currency) {
      inputValue.value = formatMoneyInputDisplay(modelValue, currency)
    }
  },
  { immediate: true },
)

function handleInput(event) {
  const normalized = normalizeMoneyInput(
    event.target.value,
    selectedCurrency.value,
  )
  const parsed = parseMoneyInput(normalized, selectedCurrency.value)

  inputValue.value = normalized
  event.target.value = normalized
  emit('update:modelValue', parsed ?? '')
  emit('input', event)
}

function handleFocus(event) {
  isFocused.value = true

  if (Number(props.modelValue) === 0) {
    inputValue.value = ''
    event.target.value = ''
  } else {
    inputValue.value = normalizeMoneyInput(
      props.modelValue,
      selectedCurrency.value,
    )
    event.target.value = inputValue.value
  }

  emit('focus', event)
}

function handleBlur(event) {
  isFocused.value = false
  inputValue.value = formatMoneyInputDisplay(
    parseMoneyInput(inputValue.value, selectedCurrency.value),
    selectedCurrency.value,
  )
  event.target.value = inputValue.value
  emit('blur', event)
}

const invalidClass = computed(() => {
  if (props.invalid) {
    return 'border-red-500 ring-red-500 focus:ring-red-500 focus:border-red-500'
  }
  return 'focus:ring-primary-400 focus:border-primary-400'
})
</script>
