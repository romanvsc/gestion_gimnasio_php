<template>
  <PaymentFormModal :member="member" @close="$emit('close')" @saved="$emit('saved')" />
</template>

<script setup>
import PaymentFormModal from '@/components/PaymentFormModal.vue'
defineProps({ member: { type: Object, required: true } })
defineEmits(['close', 'saved'])
</script>
