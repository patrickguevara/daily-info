<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import type { DashboardData } from '@/types/dashboard'
import DatePicker from '@/components/DatePicker.vue'
import NewsSection from '@/components/NewsSection.vue'
import WeatherSection from '@/components/WeatherSection.vue'
import StockSection from '@/components/StockSection.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'

const page = usePage()
const props = defineProps<DashboardData>()

const isLoading = ref(false)
const errorMessage = computed(() => page.props.flash?.error as string | undefined)
const hasNoData = computed(() => props.news.length === 0 && props.weather.length === 0 && props.stocks.length === 0)

// Show loading during navigation
watch(() => page.props, () => {
  isLoading.value = false
}, { deep: true })
</script>

<template>
  <Head title="Daily Info Dashboard" />

  <div class="min-h-screen bg-background p-6">
    <LoadingSpinner
      v-if="isLoading"
      :message="`Fetching data for ${date}...`"
    />

    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-4xl font-bold">Daily Info Dashboard</h1>
          <p class="text-muted-foreground mt-1">
            Last updated: {{ new Date(lastUpdated).toLocaleString() }}
          </p>
        </div>
        <DatePicker
          :current-date="dateParam"
          :available-dates="availableDates"
        />
      </div>

      <!-- Error Alert -->
      <Alert v-if="errorMessage" variant="destructive">
        <AlertDescription>{{ errorMessage }}</AlertDescription>
      </Alert>

      <!-- No Data Alert -->
      <Alert v-if="hasNoData && !isLoading">
        <AlertDescription>
          <div class="space-y-2">
            <p class="font-semibold">No news available for {{ date }}</p>
            <p class="text-sm">
              News articles may not be available yet for this date. NewsAPI typically indexes articles with a delay.
              Try selecting an earlier date from the date picker above, or check back later.
            </p>
          </div>
        </AlertDescription>
      </Alert>

      <!-- News Section -->
      <NewsSection :news="news" :loading="isLoading" />

      <!-- Weather and Stocks Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <WeatherSection :weather="weather" :loading="isLoading" />
        <StockSection :stocks="stocks" :loading="isLoading" />
      </div>
    </div>
  </div>
</template>
