<template>
  <a href="#app-main" class="skip-link">Saltar al contenido principal</a>
  <RouterView />
  <ConfirmDialog />
  <ToastHost />
</template>

<script setup>
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import ToastHost from '@/components/ui/ToastHost.vue'
</script>
