<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import type { DateOption } from '@/types/dashboard'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { CalendarIcon } from 'lucide-vue-next'

const props = defineProps<{
  currentDate: string
  availableDates: DateOption[]
}>()

const currentLabel = props.availableDates.find(d => d.value === props.currentDate)?.label

const handleDateChange = (value: string) => {
  router.get(`/dashboard/${value}`, {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="outline" class="w-[240px] justify-start text-left font-normal">
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ currentLabel }}
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-[240px]">
      <DropdownMenuLabel>Select a date</DropdownMenuLabel>
      <DropdownMenuItem
        v-for="date in availableDates"
        :key="date.value"
        @click="handleDateChange(date.value)"
        :class="{ 'bg-accent': date.value === currentDate }"
      >
        {{ date.label }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
