<script setup lang="ts">
defineProps<{
  title?: string
  description?: string
}>()
</script>

<template>
  <div class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <div class="flex items-start justify-between">
        <div>
          <slot name="breadcrumbs" />
          
          <slot name="title">
            <h1 v-if="title" class="text-2xl font-semibold text-slate-900">{{ title }}</h1>
          </slot>
          
          <slot name="description">
            <p v-if="description" class="mt-1 text-sm text-slate-500">{{ description }}</p>
          </slot>
        </div>

        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-3 ml-4">
          <slot name="actions" />
        </div>
      </div>
    </div>
  </div>
</template>
