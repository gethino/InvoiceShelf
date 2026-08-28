<template>
  <section v-if="showQuickLogin" class="mt-8 text-start">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">
        {{ $t('tripoli_customizations.quick_login.title') }}
      </h1>
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('tripoli_customizations.quick_login.description') }}
      </p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <div
        v-for="user in quickLoginUsers"
        :key="user.token"
        :class="selectedQuickUser?.token === user.token ? 'sm:col-span-2' : ''"
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition"
      >
        <button
          type="button"
          class="flex w-full items-center gap-3 p-3 text-start transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-400"
          :aria-expanded="selectedQuickUser?.token === user.token"
          @click="selectQuickUser(user)"
        >
          <img
            v-if="user.avatar"
            :src="user.avatar"
            :alt="user.name"
            class="h-12 w-12 shrink-0 rounded-full object-cover"
          />
          <span
            v-else
            aria-hidden="true"
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700"
          >
            {{ initials(user.name) }}
          </span>
          <span class="min-w-0 font-medium text-gray-900">
            {{ user.name }}
          </span>
        </button>

        <form
          v-if="selectedQuickUser?.token === user.token"
          class="border-t border-gray-100 p-4"
          @submit.prevent="submitQuickLogin"
        >
          <BaseInputGroup
            :error="quickLoginError"
            :label="$t('login.password')"
            class="mb-4"
            required
          >
            <BaseInput
              v-model="quickPassword"
              :type="quickInputType"
              name="quick-login-password"
              autocomplete="current-password"
              focus
            >
              <template #right>
                <BaseIcon
                  :name="quickPasswordVisible ? 'EyeIcon' : 'EyeSlashIcon'"
                  class="mr-1 cursor-pointer text-gray-500"
                  @click="quickPasswordVisible = !quickPasswordVisible"
                />
              </template>
            </BaseInput>
          </BaseInputGroup>

          <BaseButton
            class="w-full"
            :loading="quickLoginLoading"
            :disabled="quickLoginLoading"
            type="submit"
          >
            {{
              $t('tripoli_customizations.quick_login.sign_in_as', {
                name: user.name,
              })
            }}
          </BaseButton>
        </form>
      </div>
    </div>

    <button
      type="button"
      class="mt-6 text-sm font-medium text-primary-500 hover:text-primary-700"
      @click="openStandardLogin"
    >
      {{ $t('tripoli_customizations.quick_login.use_email') }}
    </button>
  </section>

  <form
    v-else
    id="loginForm"
    class="mt-8 text-left"
    @submit.prevent="onSubmit"
  >
    <button
      v-if="quickLoginUsers.length"
      type="button"
      class="mb-6 text-sm font-medium text-primary-500 hover:text-primary-700"
      @click="openQuickLogin"
    >
      {{ $t('tripoli_customizations.quick_login.back') }}
    </button>

    <BaseInputGroup
      :error="v$.email.$error && v$.email.$errors[0].$message"
      :label="$t('login.email')"
      class="mb-4"
      required
    >
      <BaseInput
        v-model="authStore.loginData.email"
        :invalid="v$.email.$error"
        focus
        type="email"
        name="email"
        @input="v$.email.$touch()"
      />
    </BaseInputGroup>

    <BaseInputGroup
      :error="v$.password.$error && v$.password.$errors[0].$message"
      :label="$t('login.password')"
      class="mb-4"
      required
    >
      <BaseInput
        v-model="authStore.loginData.password"
        :invalid="v$.password.$error"
        :type="getInputType"
        name="password"
        @input="v$.password.$touch()"
      >
        <template #right>
          <BaseIcon
            :name="isShowPassword ? 'EyeIcon' : 'EyeSlashIcon'"
            class="mr-1 text-gray-500 cursor-pointer"
            @click="isShowPassword = !isShowPassword"
          />
        </template>
      </BaseInput>
    </BaseInputGroup>

    <div class="mt-5 mb-8">
      <div class="mb-4">
        <router-link
          to="forgot-password"
          class="text-sm text-primary-400 hover:text-gray-700"
        >
          {{ $t('login.forgot_password') }}
        </router-link>
      </div>
    </div>
    <BaseButton :loading="isLoading" type="submit">
      {{ $t('login.login') }}
    </BaseButton>
  </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useRouter } from 'vue-router'
import { required, email, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/scripts/admin/stores/auth'

defineOptions({ name: 'AdminLogin' })

const notificationStore = useNotificationStore()
const authStore = useAuthStore()
const { t } = useI18n()
const router = useRouter()
const isLoading = ref(false)
let isShowPassword = ref(false)
const quickLoginUsers = computed(
  () => window.tripoli_branding?.quick_login_users || [],
)
const showQuickLogin = ref(
  quickLoginUsers.value.length > 0 && !window.demo_mode,
)
const selectedQuickUser = ref(null)
const quickPassword = ref('')
const quickLoginError = ref('')
const quickLoginLoading = ref(false)
const quickPasswordVisible = ref(false)

const rules = {
  email: {
    required: helpers.withMessage(t('validation.required'), required),
    email: helpers.withMessage(t('validation.email_incorrect'), email),
  },
  password: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => authStore.loginData)
)

const getInputType = computed(() => {
  if (isShowPassword.value) {
    return 'text'
  }
  return 'password'
})

const quickInputType = computed(() =>
  quickPasswordVisible.value ? 'text' : 'password',
)

function initials(name) {
  return name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase()
}

function selectQuickUser(user) {
  selectedQuickUser.value = user
  quickPassword.value = ''
  quickLoginError.value = ''
  quickPasswordVisible.value = false
}

function openStandardLogin() {
  showQuickLogin.value = false
  selectedQuickUser.value = null
  quickPassword.value = ''
  quickLoginError.value = ''
}

function openQuickLogin() {
  showQuickLogin.value = true
  v$.value.$reset()
}

async function submitQuickLogin() {
  if (!quickPassword.value || !selectedQuickUser.value) {
    quickLoginError.value = t('validation.required')
    return
  }

  quickLoginLoading.value = true
  quickLoginError.value = ''

  try {
    await authStore.quickLogin({
      token: selectedQuickUser.value.token,
      password: quickPassword.value,
    })
    finishLogin()
  } catch (error) {
    const errors = error.response?.data?.errors || {}
    quickLoginError.value =
      Object.values(errors).flat().find(Boolean) ||
      error.response?.data?.message ||
      t('tripoli_customizations.common.error')
  } finally {
    quickLoginLoading.value = false
  }
}

function finishLogin() {
  router.push('/admin/dashboard')

  notificationStore.showNotification({
    type: 'success',
    message: t('general.login_successfully'),
  })
}

async function onSubmit() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  isLoading.value = true

  try {
    isLoading.value = true
    await authStore.login(authStore.loginData)

    finishLogin()
  } catch (error) {
    isLoading.value = false
  }
}

// Pre-fill demo credentials if in demo environment
onMounted(() => {
  if (window.demo_mode) {
    showQuickLogin.value = false
    authStore.loginData.email = 'demo@invoiceshelf.com'
    authStore.loginData.password = 'demo'
  }
})
</script>
