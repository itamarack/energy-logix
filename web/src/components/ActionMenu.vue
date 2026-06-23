<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

defineProps<{
  label?: string
}>()

const open = ref(false)
const menuRef = ref<HTMLDivElement | null>(null)

function toggle(e: Event) {
  e.stopPropagation()
  open.value = !open.value
}

function close() {
  open.value = false
}

function handleOutsideClick(e: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleOutsideClick))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick))
</script>

<template>
  <div ref="menuRef" class="relative inline-block text-left">
    <button
      type="button"
      @click="toggle"
      class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200 transition-colors hover:bg-slate-50 hover:text-slate-900"
    >
      <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
      </svg>
      {{ label ?? 'Actions' }}
    </button>

    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute right-0 z-50 mt-1.5 w-44 origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-slate-200 focus:outline-none"
      >
        <div class="py-1" @click="close">
          <slot />
        </div>
      </div>
    </Transition>
  </div>
</template>
