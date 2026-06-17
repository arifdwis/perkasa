<script setup>
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <!-- Global Notifications & Dialogs -->
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Core Layout / Router Render -->
    <main class="flex-grow flex flex-col">
      <router-view />
    </main>
  </div>
</template>

<style>
/* Any custom override styles can go here */
</style>
