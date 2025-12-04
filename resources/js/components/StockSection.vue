<script setup lang="ts">
import type { StockData } from '@/types/dashboard'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

defineProps<{
  stocks: StockData[]
  loading?: boolean
}>()

const formatPrice = (price: number | string) => {
  const numPrice = typeof price === 'string' ? parseFloat(price) : price
  return `$${numPrice.toFixed(2)}`
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <span class="text-2xl">📈</span>
      <h2 class="text-2xl font-bold">Stocks</h2>
    </div>

    <div v-if="loading">
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-4">
            <div v-for="i in 5" :key="i" class="flex justify-between items-center">
              <Skeleton class="h-4 w-16" />
              <Skeleton class="h-4 w-24" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div v-else-if="stocks.length === 0" class="text-center py-8">
      <p class="text-muted-foreground">No stock data available</p>
    </div>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-lg">Market Data</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div
            v-for="stock in stocks"
            :key="stock.id"
            class="flex justify-between items-center py-2 border-b last:border-0"
          >
            <div>
              <div class="font-semibold">{{ stock.ticker_symbol }}</div>
              <div class="text-sm text-muted-foreground">{{ stock.company_name }}</div>
            </div>
            <div class="text-right">
              <div class="font-semibold">{{ formatPrice(stock.price) }}</div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
