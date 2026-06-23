<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import BuyerBottomNav from '../components/BuyerBottomNav.vue'
import PWAInstallButton from '../components/PWAInstallButton.vue'

const route = useRoute()
const showBottomNav = computed(() => {
  return !['ProductDetail'].includes(route.name)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Main Content Area -->
    <main class="flex-grow flex flex-col">
      <router-view />
    </main>

    <!-- PWA Install Button -->
    <PWAInstallButton />

    <!-- Mobile Bottom Navigation -->
    <BuyerBottomNav v-if="showBottomNav" />
  </div>
</template>
