import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useFilterStore = defineStore('filters', () => {
  const formulaSearch = ref('')
  const contractSearch = ref('')
  const calculationSearch = ref('')
  
  return { 
    formulaSearch, 
    contractSearch,
    calculationSearch
  }
})
