<script setup lang="ts">
import type { NewsArticle } from '@/types/dashboard'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

defineProps<{
  news: NewsArticle[]
  loading?: boolean
}>()
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <span class="text-2xl">📰</span>
      <h2 class="text-2xl font-bold">Top News Headlines</h2>
    </div>

    <div v-if="loading" class="space-y-4">
      <Card v-for="i in 5" :key="i">
        <CardHeader>
          <Skeleton class="h-6 w-3/4" />
          <Skeleton class="h-4 w-1/2" />
        </CardHeader>
      </Card>
    </div>

    <div v-else-if="news.length === 0" class="text-center py-12">
      <p class="text-muted-foreground">No news available for this date</p>
    </div>

    <div v-else class="space-y-4">
      <Card v-for="article in news" :key="article.id">
        <CardHeader>
          <CardTitle class="text-lg">
            <a
              :href="article.url"
              target="_blank"
              rel="noopener noreferrer"
              class="hover:text-primary transition-colors"
            >
              {{ article.headline }}
            </a>
          </CardTitle>
          <CardDescription>
            {{ article.source }} • {{ new Date(article.published_at).toLocaleDateString() }}
          </CardDescription>
        </CardHeader>
        <CardContent v-if="article.description">
          <p class="text-sm text-muted-foreground">
            {{ article.description }}
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
