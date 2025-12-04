<script setup lang="ts">
import type { WeatherData } from '@/types/dashboard'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const props = defineProps<{
  weather: WeatherData[]
  loading?: boolean
}>()

const formatTemperature = (temp: number | string) => {
  const celsius = typeof temp === 'string' ? parseFloat(temp) : temp
  const fahrenheit = (celsius * 9/5) + 32
  return `${Math.round(celsius)}°C / ${Math.round(fahrenheit)}°F`
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <span class="text-2xl">🌤</span>
      <h2 class="text-2xl font-bold">Weather</h2>
    </div>

    <div v-if="loading" class="space-y-4">
      <Card v-for="i in 3" :key="i">
        <CardHeader>
          <Skeleton class="h-6 w-32" />
        </CardHeader>
        <CardContent>
          <Skeleton class="h-4 w-24" />
        </CardContent>
      </Card>
    </div>

    <div v-else-if="weather.length === 0" class="text-center py-8">
      <p class="text-muted-foreground">No weather data available</p>
    </div>

    <div v-else class="space-y-4">
      <Card v-for="item in weather" :key="item.id">
        <CardHeader>
          <CardTitle class="text-lg">{{ item.location }}</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <span class="text-2xl font-bold">{{ formatTemperature(item.temperature) }}</span>
            <span class="text-muted-foreground capitalize">{{ item.description }}</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
