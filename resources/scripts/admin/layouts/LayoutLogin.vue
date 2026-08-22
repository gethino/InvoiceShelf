<template>
  <div class="grid h-screen grid-cols-12 overflow-y-auto bg-gray-100">
    <NotificationRoot />

    <div
      :class="
        simplifiedLogin
          ? 'col-span-12 p-4 py-8 md:p-8'
          : 'col-span-12 p-4 md:col-span-6 md:p-8 md:pb-48 md:pt-40 lg:col-span-4'
      "
      class="
        flex
        items-center
        justify-center
        w-full
        mx-auto
        text-gray-900
        flex-2
      "
    >
      <div class="w-full max-w-md">
        <MainLogo
          v-if="!loginPageLogo"
          class="mb-10 block h-auto w-48 max-w-full text-primary-500"
        />

        <img
          v-else
          :src="loginPageLogo"
          class="mb-10 block h-auto w-48 max-w-full text-primary-500"
        />

        <router-view />

        <div
          class="
            pt-10
            mt-0
            text-sm
            not-italic
            font-medium
            leading-relaxed
            text-left text-gray-400
            md:pt-12
          "
        >
          <p class="mb-3">
            {{ copyrightText }}
            {{ new Date().getFullYear() }}
          </p>
        </div>
      </div>
    </div>
    <div
      v-if="!simplifiedLogin"
      class="
        relative
        flex-col
        items-center
        justify-center
        hidden
        w-full
        h-full
        pl-10
        bg-no-repeat bg-cover
        md:col-span-6
        lg:col-span-8
        md:flex
        content-box
        overflow-hidden
      "
    >
      <LoginBackground class="absolute h-full w-full" />

      <LoginPlanetCrater
        class="absolute z-10 top-0 right-0 h-[300px] w-[420px]"
      />

      <LoginBackgroundOverlay class="absolute h-full w-full right-[7.5%]" />

      <div class="md:pl-10 xl:pl-0 relative z-50 w-7/12 xl:w-5/12 xl:w-5/12">
        <h1
          class="
            hidden
            mb-3
            text-3xl
            leading-normal
            text-left text-white
            xl:text-5xl xl:leading-tight
            md:none
            lg:block
          "
        >
          {{ pageHeading }}
        </h1>
        <p
          class="
            hidden
            text-sm
            not-italic
            font-normal
            leading-normal
            text-left text-gray-100
            xl:text-base xl:leading-6
            md:none
            lg:block
          "
        >
          {{ pageDescription }}
        </p>
      </div>

      <LoginBottomVector
        class="
          absolute
          z-50
          w-full
          bg-no-repeat
          content-bottom
          h-[15vw]
          lg:h-[22vw]
          right-[32%]
          bottom-0
        "
      />
    </div>
  </div>
</template>

<script setup>
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'
import LoginBackground from '@/scripts/components/svg/LoginBackground.vue'
import LoginPlanetCrater from '@/scripts/components/svg/LoginPlanetCrater.vue'
import LoginBottomVector from '@/scripts/components/svg/LoginBottomVector.vue'
import LoginBackgroundOverlay from '@/scripts/components/svg/LoginBackgroundOverlay.vue'
import { computed, ref } from 'vue'

const pageHeading = computed(() => {
  if (window.login_page_heading) {
    return window.login_page_heading
  }

  return 'Simple Invoicing for Individuals Small Businesses'
})

const pageDescription = computed(() => {
  if (window.login_page_description) {
    return window.login_page_description
  }

  return 'InvoiceShelf helps you track expenses, record payments & generate beautiful invoices & estimates.'
})

const copyrightText = computed(() => {
  if (window.copyright_text) {
    return window.copyright_text
  }
  return 'Copyright @ IDEOLOGIX MEDIA DOOEL.'
})

const loginPageLogo = computed(() => {
  if (window.login_page_logo) {
    return window.login_page_logo
  }

  return false
})

const simplifiedLogin = computed(
  () =>
    window.TripoliCustomizations?.isSimplifiedLogin(window.tripoli_branding) ??
    false,
)
</script>

<style scoped>
</style>
