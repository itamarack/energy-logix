import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/pages/Home.vue'),
    },
    {
      path: '/formula-versions',
      name: 'formula-versions.index',
      component: () => import('@/pages/FormulaVersions/Index.vue'),
    },
    {
      path: '/formula-versions/create',
      name: 'formula-versions.create',
      component: () => import('@/pages/FormulaVersions/Create.vue'),
    },
    {
      path: '/formula-versions/:id',
      name: 'formula-versions.show',
      component: () => import('@/pages/FormulaVersions/Show.vue'),
    },
    {
      path: '/formula-versions/:id/edit',
      name: 'formula-versions.edit',
      component: () => import('@/pages/FormulaVersions/Edit.vue'),
    },
    {
      path: '/contracts',
      name: 'contracts.index',
      component: () => import('@/pages/Contracts/Index.vue'),
    },
    {
      path: '/contracts/:id',
      name: 'contracts.show',
      component: () => import('@/pages/Contracts/Show.vue'),
    },
    {
      path: '/calculations',
      name: 'calculations.index',
      component: () => import('@/pages/Calculations/Index.vue'),
    },
    {
      path: '/calculations/:id',
      name: 'calculations.show',
      component: () => import('@/pages/Calculations/Show.vue'),
    },
  ],
})

export default router
