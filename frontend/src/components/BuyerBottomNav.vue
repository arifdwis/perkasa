<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const cartStore = useCartStore()

const isRouteActive = (name) => {
  return route.name === name
}
</script>

<template>
  <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-100 shadow-lg py-2 px-2 flex justify-around items-center z-40 select-none pb-safe-bottom">
    <!-- Beranda -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('BuyerHome') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'BuyerHome' })"
    >
      <Icon 
        :icon="isRouteActive('BuyerHome') ? 'solar:home-2-bold' : 'solar:home-2-linear'" 
        class="text-xl" 
      />
      <span class="text-[10px] font-extrabold uppercase">Beranda</span>
    </div>

    <!-- Katalog -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('Catalog') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'Catalog' })"
    >
      <Icon 
        :icon="isRouteActive('Catalog') ? 'solar:compass-square-bold' : 'solar:compass-square-linear'" 
        class="text-xl" 
      />
      <span class="text-[10px] font-extrabold uppercase">Katalog</span>
    </div>

    <!-- Keranjang (Cart) -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors relative"
      :class="isRouteActive('Cart') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'Cart' })"
    >
      <Icon 
        :icon="isRouteActive('Cart') ? 'solar:cart-large-minimalistic-bold' : 'solar:cart-large-minimalistic-linear'" 
        class="text-xl" 
      />
      <span class="text-[10px] font-extrabold uppercase">Keranjang</span>
      <span 
        v-if="cartStore.cartCount > 0" 
        class="absolute -top-1 right-2 bg-primary text-white text-[7px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white"
      >
        {{ cartStore.cartCount }}
      </span>
    </div>

    <!-- Favorit -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('Favorites') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'Favorites' })"
    >
      <Icon 
        :icon="isRouteActive('Favorites') ? 'solar:heart-bold' : 'solar:heart-linear'" 
        class="text-xl" 
      />
      <span class="text-[10px] font-extrabold uppercase">Favorit</span>
    </div>

    <!-- Pesanan -->
    <div 
      class="flex flex-col items-center gap-1 cursor-pointer w-14 text-center transition-colors"
      :class="isRouteActive('BuyerOrders') ? 'text-primary' : 'text-slate-400'"
      @click="router.push({ name: 'BuyerOrders' })"
    >
      <Icon 
        :icon="isRouteActive('BuyerOrders') ? 'solar:clipboard-list-bold' : 'solar:clipboard-list-linear'" 
        class="text-xl" 
      />
      <span class="text-[10px] font-extrabold uppercase">Pesanan</span>
    </div>
  </nav>
</template>

<style scoped>
.pb-safe-bottom {
  padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
}
</style>
