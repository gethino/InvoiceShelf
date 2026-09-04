<template>
  <BaseInputGroup
    :label="$t('tripoli_customizations.customer_organization.label')"
    :content-loading="contentLoading"
  >
    <BaseMultiselect
      v-model="selectedOrganizationId"
      :options="organizations"
      :content-loading="contentLoading || isLoading"
      value-prop="id"
      label="name"
      track-by="name"
      searchable
      :can-clear="true"
      :can-deselect="true"
      :placeholder="
        $t('tripoli_customizations.customer_organization.placeholder')
      "
    >
      <template #action>
        <BaseSelectAction @click.stop="openCreateModal">
          <BaseIcon
            name="PlusIcon"
            class="me-2 -ms-2 h-4 text-center text-primary-400"
          />
          {{ $t('tripoli_customizations.customer_organization.create') }}
        </BaseSelectAction>
      </template>
    </BaseMultiselect>

    <p v-if="loadError" class="mt-2 text-sm text-red-600">
      {{ loadError }}
    </p>
  </BaseInputGroup>

  <BaseModal :show="isCreateModalOpen" @close="closeCreateModal">
    <template #header>
      <div class="flex w-full items-center justify-between">
        {{ $t('tripoli_customizations.customer_organization.create_title') }}
        <BaseIcon
          name="XMarkIcon"
          class="h-6 w-6 cursor-pointer text-gray-500"
          @click="closeCreateModal"
        />
      </div>
    </template>

    <form @submit.prevent="createOrganization">
      <div class="space-y-5 p-6">
        <BaseInputGroup
          :label="$t('tripoli_customizations.customer_organization.name')"
          required
        >
          <BaseInput v-model.trim="newOrganization.name" required />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('tripoli_customizations.customer_organization.notes')"
        >
          <BaseTextarea v-model.trim="newOrganization.notes" rows="3" />
        </BaseInputGroup>

        <p v-if="createError" class="text-sm text-red-600">
          {{ createError }}
        </p>
      </div>

      <div
        class="flex justify-end gap-3 border-t border-gray-200 border-solid p-4"
      >
        <BaseButton
          type="button"
          variant="primary-outline"
          @click="closeCreateModal"
        >
          {{ $t('tripoli_customizations.customer_organization.cancel') }}
        </BaseButton>
        <BaseButton type="submit" :loading="isCreating" :disabled="isCreating">
          {{ $t('tripoli_customizations.customer_organization.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import http from '@/scripts/http'

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: null,
  },
  contentLoading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const organizations = ref([])
const isLoading = ref(false)
const isCreating = ref(false)
const isCreateModalOpen = ref(false)
const loadError = ref('')
const createError = ref('')
const newOrganization = reactive({ name: '', notes: '' })

const selectedOrganizationId = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value || null),
})

onMounted(loadOrganizations)

async function loadOrganizations() {
  isLoading.value = true
  loadError.value = ''

  try {
    const { data } = await http.get('/api/v1/customer-organizations')
    organizations.value = data.data
  } catch {
    loadError.value = window.i18n.global.t(
      'tripoli_customizations.common.error',
    )
  } finally {
    isLoading.value = false
  }
}

function openCreateModal() {
  createError.value = ''
  isCreateModalOpen.value = true
}

function closeCreateModal() {
  isCreateModalOpen.value = false
  createError.value = ''
  newOrganization.name = ''
  newOrganization.notes = ''
}

async function createOrganization() {
  isCreating.value = true
  createError.value = ''

  try {
    const { data } = await http.post(
      '/api/v1/customer-organizations',
      newOrganization,
    )
    organizations.value.push(data.data)
    organizations.value.sort((first, second) =>
      first.name.localeCompare(second.name),
    )
    emit('update:modelValue', data.data.id)
    closeCreateModal()
  } catch (error) {
    createError.value =
      error.response?.data?.errors?.name?.[0] ||
      window.i18n.global.t('tripoli_customizations.common.error')
  } finally {
    isCreating.value = false
  }
}
</script>
