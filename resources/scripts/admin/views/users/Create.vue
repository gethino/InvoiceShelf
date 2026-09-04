<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('users.user', 2)" to="/admin/users" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form action="" autocomplete="off" @submit.prevent="submitUser">
      <div class="grid grid-cols-12">
        <BaseCard class="mt-6 col-span-12 md:col-span-8">
          <BaseInputGrid layout="one-column">
            <BaseInputGroup
              :content-loading="isFetchingInitialData"
              :label="$t('users.name')"
              :error="
                v$.userData.name.$error && v$.userData.name.$errors[0].$message
              "
              required
            >
              <BaseInput
                v-model.trim="userStore.userData.name"
                :content-loading="isFetchingInitialData"
                :invalid="v$.userData.name.$error"
                @input="v$.userData.name.$touch()"
              >
              </BaseInput>
            </BaseInputGroup>

            <BaseInputGroup
              :content-loading="isFetchingInitialData"
              :label="$t('users.email')"
              :error="
                v$.userData.email.$error &&
                v$.userData.email.$errors[0].$message
              "
              required
            >
              <BaseInput
                v-model.trim="userStore.userData.email"
                type="email"
                :content-loading="isFetchingInitialData"
                :invalid="v$.userData.email.$error"
                @input="v$.userData.email.$touch()"
              >
              </BaseInput>
            </BaseInputGroup>

            <BaseInputGroup
              v-if="currentUser.can_manage_user_companies"
              :content-loading="isFetchingInitialData"
              :label="$t('users.companies')"
              :error="
                v$.userData.companies.$error &&
                v$.userData.companies.$errors[0].$message
              "
              required
            >
              <BaseMultiselect
                v-model="userStore.userData.companies"
                mode="tags"
                :object="true"
                autocomplete="new-password"
                label="name"
                :options="companies"
                value-prop="id"
                :invalid="v$.userData.companies.$error"
                :content-loading="isFetchingInitialData"
                searchable
                :can-deselect="false"
                class="w-full"
                track-by="name"
              />
            </BaseInputGroup>

            <BaseInputGroup
              v-else
              :content-loading="isFetchingInitialData"
              :label="$t('users.companies')"
            >
              <div
                class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
              >
                {{ companyStore.selectedCompany?.name }}
              </div>
            </BaseInputGroup>

            <ValidateEach
              v-for="(company, i) in userStore.userData.companies"
              :key="i"
              :state="company"
              :rules="companyArrayRules"
            >
              <template #default="{ v }">
                <div class="space-y-6">
                  <BaseInputGroup
                    :content-loading="isFetchingInitialData"
                    :label="
                      $t('users.select_company_role', { company: company.name })
                    "
                    :error="v.role.$error && v.role.$errors[0].$message"
                    required
                  >
                    <BaseMultiselect
                      v-model="userStore.userData.companies[i].role"
                      value-prop="name"
                      track-by="id"
                      autocomplete="off"
                      :content-loading="isFetchingInitialData"
                      label="name"
                      :options="userStore.userData.companies[i].roles"
                      :can-deselect="false"
                      :invalid="v.role.$invalid"
                      @change="v.role.$touch()"
                    />
                  </BaseInputGroup>
                </div>
              </template>
            </ValidateEach>

            <BaseInputGroup
              :content-loading="isFetchingInitialData"
              :label="$t('users.password')"
              :error="
                v$.userData.password.$error &&
                v$.userData.password.$errors[0].$message
              "
              :required="!isEdit"
            >
              <BaseInput
                v-model="userStore.userData.password"
                name="new-password"
                autocomplete="new-password"
                :content-loading="isFetchingInitialData"
                type="password"
                :invalid="v$.userData.password.$error"
                @input="v$.userData.password.$touch()"
              >
              </BaseInput>
            </BaseInputGroup>

            <BaseInputGroup
              :content-loading="isFetchingInitialData"
              :label="$t('users.phone')"
            >
              <BaseInput
                v-model.trim="userStore.userData.phone"
                :content-loading="isFetchingInitialData"
              ></BaseInput>
            </BaseInputGroup>
          </BaseInputGrid>

          <BaseButton
            :content-loading="isFetchingInitialData"
            type="submit"
            :loading="isSaving"
            :disabled="isSaving"
            class="mt-6"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                name="ArrowDownOnSquareIcon"
                :class="slotProps.class"
              />
            </template>
            {{ isEdit ? $t('users.update_user') : $t('users.save_user') }}
          </BaseButton>
        </BaseCard>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import {
  required,
  minLength,
  email,
  requiredIf,
  helpers,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { ValidateEach } from '@vuelidate/components'
import { useI18n } from 'vue-i18n'
import { useUsersStore } from '@/scripts/admin/stores/users'
import { useUserStore } from '@/scripts/admin/stores/user'

const userStore = useUsersStore()
const currentUserStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const companyStore = useCompanyStore()
const currentUser = computed(() => currentUserStore.currentUser)

let isSaving = ref(false)
let isFetchingInitialData = ref(false)
let selectedCompanies = ref([])
let companies = ref([])

const isEdit = computed(() => route.name === 'users.edit')

const pageTitle = computed(() =>
  isEdit.value ? t('users.edit_user') : t('users.new_user'),
)

const rules = computed(() => {
  return {
    userData: {
      name: {
        required: helpers.withMessage(t('validation.required'), required),
        minLength: helpers.withMessage(
          t('validation.name_min_length', { count: 3 }),
          minLength(3),
        ),
      },
      email: {
        required: helpers.withMessage(t('validation.required'), required),
        email: helpers.withMessage(t('validation.email_incorrect'), email),
      },
      password: {
        required: requiredIf(function () {
          helpers.withMessage(t('validation.required'), required)
          return !isEdit.value
        }),
        minLength: helpers.withMessage(
          t('validation.password_min_length', { count: 8 }),
          minLength(8),
        ),
      },
      companies: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const companyArrayRules = {
  role: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}

const v$ = useVuelidate(rules, userStore, {
  $scope: true,
})

loadInitialData()

userStore.resetUserData()

async function loadInitialData() {
  isFetchingInitialData.value = true
  try {
    if (isEdit.value) {
      await userStore.fetchUser(route.params.id)
    }

    if (currentUser.value.can_manage_user_companies) {
      let res = await companyStore.fetchUserCompanies()

      if (res?.data?.data) {
        companies.value = res.data.data.map((company) => ({
          ...company,
          role: null,
        }))
      }
    } else {
      const selectedCompany = companyStore.selectedCompany
      const assignedCompany = userStore.userData.companies.find(
        (company) => company.id === selectedCompany.id,
      )
      const company = {
        ...selectedCompany,
        role: assignedCompany?.role || null,
        roles: selectedCompany.roles.filter(
          (role) => role.name !== 'super admin',
        ),
      }

      companies.value = [company]
      userStore.userData.companies = [company]
    }
  } catch {
    isFetchingInitialData.value = false
  }

  isFetchingInitialData.value = false
}

async function submitUser() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  try {
    isSaving.value = true
    let data = {
      ...userStore.userData,
      companies: userStore.userData.companies.map((c) => {
        return {
          role: c.role,
          id: c.id,
        }
      }),
    }

    const action = isEdit.value ? userStore.updateUser : userStore.addUser
    await action(data)

    router.push('/admin/users')
    isSaving.value = false
  } catch (error) {
    isSaving.value = false
  }
}
</script>
