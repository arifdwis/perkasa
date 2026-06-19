<script setup>
import { useRouter, useRoute } from 'vue-router'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()

const isRouteActive = (name) => {
  return route.name === name || (name === 'SellerOrders' && route.name === 'SellerOrderDetail')
}
</script>

<template>
  <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-100 shadow-lg py-2 px-2 flex justify-around items-center z-40 select-none pb-safe-bottom">
    <!-- Dashboard -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('SellerHome') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'SellerHome' })"
    >
      <Icon 
        :icon="isRouteActive('SellerHome') ? 'solar:chart-square-bold' : 'solar:chart-square-linear'" 
        class="text-xl" 
      />
      <span class="text-[9px] font-extrabold uppercase">Dashboard</span>
    </div>

    <!-- Produk -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('SellerProducts') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'SellerProducts' })"
    >
      <Icon 
        :icon="isRouteActive('SellerProducts') ? 'solar:box-bold' : 'solar:box-linear'" 
        class="text-xl" 
      />
      <span class="text-[9px] font-extrabold uppercase">Produk</span>
    </div>

    <!-- Jasa -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('SellerServices') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'SellerServices' })"
    >
      <Icon 
        :icon="isRouteActive('SellerServices') ? 'solar:case-bold' : 'solar:case-linear'" 
        class="text-xl" 
      />
      <span class="text-[9px] font-extrabold uppercase">Jasa</span>
    </div>

    <!-- Pesanan Masuk -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('SellerOrders') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'SellerOrders' })"
    >
      <Icon 
        :icon="isRouteActive('SellerOrders') ? 'solar:bill-list-bold' : 'solar:bill-list-linear'" 
        class="text-xl" 
      />
      <span class="text-[9px] font-extrabold uppercase">Pesanan</span>
    </div>

    <!-- Toko Saya -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('SellerStore') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'SellerStore' })"
    >
      <Icon 
        :icon="isRouteActive('SellerStore') ? 'solar:shop-bold' : 'solar:shop-linear'" 
        class="text-xl" 
      />
      <span class="text-[9px] font-extrabold uppercase">Toko Saya</span>
    </div>
  </nav>
</template>

<style scoped>
.pb-safe-bottom {
  padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
}
</style>
