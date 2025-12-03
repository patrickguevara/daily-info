<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner'

defineProps<{
  message?: string
}>()
</script>

<template>
  <div class="fixed inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="flex flex-col items-center gap-4">
      <Spinner class="h-12 w-12" />
      <p v-if="message" class="text-muted-foreground">
        {{ message }}
      </p>
    </div>
  </div>
</template>
