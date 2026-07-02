<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useChatStore } from '../../stores/chat'
import { useAuthStore } from '../../stores/auth'
import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const chatStore = useChatStore()
const authStore = useAuthStore()
const loading = ref(true)
const conversations = ref([])

const isSellerView = computed(() => route.name === 'SellerChatList')

const getOtherParticipant = (convo) => {
  const userId = authStore.user?.id
  if (!userId || !convo.participants) return null
  return convo.participants.find(p => p.user_id !== userId)
}

const getChatTitle = (convo) => {
  if (isSellerView.value) {
    const other = getOtherParticipant(convo)
    return other?.user?.name || 'Pembeli'
  }
  return convo.store?.name || 'Toko'
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await axios.get('/chat/conversations')
    conversations.value = res.data.data || []
  } catch (err) {
    console.error('Failed to fetch conversations', err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

const formatTime = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const now = new Date()
  const diff = now - d
  if (diff < 86400000 && now.getDate() === d.getDate()) {
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
  }
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <AppNavbar v-if="!isSellerView" />

    <main class="max-w-2xl mx-auto w-full px-4 py-8 flex-grow space-y-6" :class="isSellerView ? 'pt-2' : 'pb-24 lg:pb-8'">
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-lg sm:text-xl font-black text-slate-800 flex items-center gap-2">
            <Icon icon="solar:chat-round-dots-bold-duotone" class="text-primary text-xl" />
            Pesan Chat
          </h2>
          <p class="text-xs text-slate-400 font-medium mt-0.5">Percakapan dengan penjual dan pembeli</p>
        </div>
      </div>

      <LoadingState v-if="loading" message="Memuat percakapan..." />

      <EmptyState
        v-else-if="conversations.length === 0"
        icon="pi-comments"
        title="Belum ada percakapan"
        :description="isSellerView ? 'Pembeli akan memulai chat dari halaman produk toko Anda.' : 'Mulai percakapan dari halaman produk atau kunjungi toko untuk bertanya langsung ke penjual.'"
        :actionLabel="isSellerView ? 'Kelola Produk' : 'Jelajahi Katalog'"
        :action="isSellerView ? () => router.push({ name: 'SellerProducts' }) : undefined"
        @action="router.push({ name: 'Catalog' })"
      />

      <div v-else class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-50">
        <div
          v-for="convo in conversations"
          :key="convo.id"
          class="px-4 py-4 flex items-center gap-3.5 cursor-pointer hover:bg-slate-50/50 transition-colors"
          :class="{ 'bg-primary/5': convo.unread_count > 0 }"
          @click="router.push({ name: isSellerView ? 'SellerChatDetail' : 'ChatDetail', params: { id: convo.id } })"
        >
          <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center shadow-xs">
            <template v-if="isSellerView">
              <Icon icon="solar:user-circle-bold" class="text-slate-400 text-2xl" />
            </template>
            <template v-else>
              <img v-if="convo.store?.logo" :src="convo.store.logo" alt="" class="w-full h-full object-cover" />
              <Icon v-else icon="solar:shop-bold" class="text-slate-400 text-lg" />
            </template>
          </div>

          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-sm font-bold text-slate-800 truncate">{{ getChatTitle(convo) }}</h4>
              <span class="text-[10px] text-slate-400 font-medium shrink-0">{{ formatTime(convo.last_message?.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between mt-1">
              <p class="text-xs text-slate-500 truncate flex-1 min-w-0 leading-relaxed">
                <span v-if="convo.product" class="inline-flex items-center gap-1 text-primary font-semibold mr-1.5 text-[10px] bg-primary/5 px-1.5 py-0.5 rounded-md">
                  <Icon icon="solar:box-linear" class="text-[10px]" /> {{ convo.product.name }}
                </span>
                <span :class="{ 'font-semibold text-slate-700': convo.unread_count > 0 }">
                  {{ convo.last_message?.text || 'Mulai percakapan' }}
                </span>
              </p>
              <span
                v-if="convo.unread_count > 0"
                class="bg-primary text-white text-[10px] font-bold min-w-[20px] h-5 rounded-full flex items-center justify-center shrink-0 ml-2 px-1.5"
              >{{ convo.unread_count > 99 ? '99+' : convo.unread_count }}</span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
