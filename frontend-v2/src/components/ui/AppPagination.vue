<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  IconChevronLeft,
  IconChevronRight,
  IconChevronsLeft,
  IconChevronsRight,
} from '@tabler/icons-vue'

const props = withDefaults(defineProps<{
  currentPage: number
  lastPage: number
  total: number
  from?: number | null
  to?: number | null
  perPage?: number
  perPageOptions?: number[]
}>(), {
  perPageOptions: () => [10, 15, 25, 50, 100],
})

const emit = defineEmits<{
  'update:currentPage': [page: number]
  'update:perPage': [perPage: number]
}>()

const hasPrev = computed(() => props.currentPage > 1)
const hasNext = computed(() => props.currentPage < props.lastPage)

const visiblePages = computed(() => {
  const pages: (number | '...')[] = []
  const current = props.currentPage
  const last = props.lastPage

  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
    return pages
  }

  pages.push(1)

  if (current > 3) {
    pages.push('...')
  }

  const start = Math.max(2, current - 1)
  const end = Math.min(last - 1, current + 1)

  for (let i = start; i <= end; i++) {
    pages.push(i)
  }

  if (current < last - 2) {
    pages.push('...')
  }

  pages.push(last)
  return pages
})

function goToPage(page: number): void {
  if (page < 1 || page > props.lastPage || page === props.currentPage) return
  emit('update:currentPage', page)
}

function handlePerPageChange(value: string): void {
  emit('update:perPage', Number(value))
}
</script>

<template>
  <div v-if="total > 0" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    <!-- Info text -->
    <div class="text-sm text-muted-foreground">
      <template v-if="from != null && to != null">
        Showing <span class="font-medium text-foreground">{{ from }}</span>
        to <span class="font-medium text-foreground">{{ to }}</span>
        of <span class="font-medium text-foreground">{{ total }}</span> results
      </template>
      <template v-else>
        <span class="font-medium text-foreground">{{ total }}</span> results
      </template>
    </div>

    <div class="flex items-center gap-4">
      <!-- Per page selector -->
      <div v-if="perPage" class="flex items-center gap-2">
        <span class="text-sm text-muted-foreground">Per page</span>
        <Select :model-value="String(perPage)" @update:model-value="handlePerPageChange">
          <SelectTrigger class="w-[70px]" size="sm">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="opt in perPageOptions" :key="opt" :value="String(opt)">
              {{ opt }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Page buttons -->
      <nav v-if="lastPage > 1" class="flex items-center gap-1" aria-label="Pagination">
        <Button
          variant="outline"
          size="icon-sm"
          :disabled="!hasPrev"
          class="hidden lg:inline-flex"
          aria-label="First page"
          @click="goToPage(1)"
        >
          <IconChevronsLeft class="h-4 w-4" />
        </Button>

        <Button
          variant="outline"
          size="icon-sm"
          :disabled="!hasPrev"
          aria-label="Previous page"
          @click="goToPage(currentPage - 1)"
        >
          <IconChevronLeft class="h-4 w-4" />
        </Button>

        <template v-for="(page, index) in visiblePages" :key="index">
          <span
            v-if="page === '...'"
            class="px-1 text-muted-foreground"
          >
            ...
          </span>
          <Button
            v-else
            :variant="page === currentPage ? 'default' : 'outline'"
            size="icon-sm"
            :class="page === currentPage ? 'bg-primary-600 text-white hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400 dark:text-gray-950' : ''"
            :aria-current="page === currentPage ? 'page' : undefined"
            @click="goToPage(page)"
          >
            {{ page }}
          </Button>
        </template>

        <Button
          variant="outline"
          size="icon-sm"
          :disabled="!hasNext"
          aria-label="Next page"
          @click="goToPage(currentPage + 1)"
        >
          <IconChevronRight class="h-4 w-4" />
        </Button>

        <Button
          variant="outline"
          size="icon-sm"
          :disabled="!hasNext"
          class="hidden lg:inline-flex"
          aria-label="Last page"
          @click="goToPage(lastPage)"
        >
          <IconChevronsRight class="h-4 w-4" />
        </Button>
      </nav>
    </div>
  </div>
</template>
