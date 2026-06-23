<script setup lang="ts">
defineProps<{
  title: string
  value: string | number
  suffix?: string
}>()
</script>

<template>
  <div class="premium-card !p-5">
    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">{{ title }}</p>
    <p class="mt-2 text-lg font-bold text-slate-900">
      {{ value }}
      <span v-if="suffix" class="text-xs font-normal text-slate-400 ml-1">{{ suffix }}</span>
    </p>
  </div>
</template>
