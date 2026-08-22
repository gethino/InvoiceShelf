<script setup>
defineProps({
  src: {
    type: [String, Boolean],
    default: false,
  },
  title: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <div
    class="flex h-[75vh] min-h-[32rem] flex-col overflow-hidden rounded-md border border-gray-300 bg-white"
  >
    <iframe
      v-if="src"
      :src="src"
      :title="title"
      class="min-h-0 flex-1 bg-white"
    />
  </div>
</template>
