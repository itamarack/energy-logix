<script setup lang="ts">
defineProps<{
  active: boolean
}>()
</script>

<template>
  <span
    :class="active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-slate-100/80 text-slate-600'"
    class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider"
  >
    <span v-if="active" class="mr-2 relative flex h-2 w-2">
      <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
      <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
    </span>
    {{ active ? 'Active' : 'Inactive' }}
  </span>
</template>
