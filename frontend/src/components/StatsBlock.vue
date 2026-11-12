<template>
  <v-row class="mb-8">
    <v-col v-for="block in blocksData" :key="block.title" cols="12" xs="12" sm="6" md="6" lg="3">
      <v-card :class="`stat-card--${block.color}`" class="stat-card pa-4 rounded-xl h-100" elevation="0">
        <div class="d-flex align-center ga-4">
          <div :class="`stat-icon--${block.color}`" class="stat-icon d-flex align-center justify-center rounded-circle">
            <v-icon :icon="block.icon" :color="block.color" size="20" />
          </div>
          <div class="stat-title">{{ block.title }}</div>
        </div>
        <div class="stat-value text-center font-weight-medium">
          {{ block.total ? block.total : `$${block.value?.[0]?.convertedPrice}` }}
        </div>
        <template v-if="block.value">
          <v-divider class="mt-1 mb-3" />
          <div class="d-flex flex-wrap justify-center ga-3">
            <div v-for="country in block.value" :key="country.rel" class="d-flex ga-2 align-center">
              <span :class="`fi fi-${country.rel === 'UK' ? 'gb' : country.rel.toLowerCase()}`" />
              <span>{{ country.title }}</span>
            </div>
          </div>
        </template>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    summary: TSummaryItem[]
  }>()

  const blocksData = computed(() => [
    { title: 'Highest Price', value: getHighestPrice(), icon: 'fas fa-arrow-up', color: 'red' },
    { title: 'Lowest Price', value: getLowestPrice(), icon: 'fas fa-arrow-down', color: 'green' },
    { title: 'Global Median', total: `$${getMedian()}`, icon: 'fas fa-dollar-sign', color: 'lime' },
    { title: 'Regions count', total: props.summary?.length, icon: 'fas fa-calculator', color: 'lime' },
  ])

  const getHighestPrice = () => {
    const maxPrice = Math.max(...props.summary.map((el) => el.convertedPrice))
    return props.summary.filter((el) => el.convertedPrice === maxPrice)
  }

  const getLowestPrice = () => {
    const minPrice = Math.min(...props.summary.map((el) => el.convertedPrice))
    return props.summary.filter((el) => el.convertedPrice === minPrice)
  }

  const getMedian = () => {
    const sorted = props.summary.map((el) => el.convertedPrice).sort((a, b) => a - b)
    const middle = Math.floor(sorted.length / 2)

    if (sorted.length % 2 === 0) return (sorted[middle - 1] + sorted[middle]) / 2
    return sorted[middle]
  }
</script>

<style lang="scss" scoped>
  .stat-card {
    background: rgba(25, 27, 30, 0.85) !important;
    border: 1px solid rgba(255, 255, 255, 0.15);

    &--red {
      border-color: rgba(255, 75, 85, 0.65);
    }

    &--green {
      border-color: rgba(35, 210, 125, 0.7);
    }

    &--lime {
      border-color: rgba(165, 195, 65, 0.7);
    }
  }

  .stat-icon {
    width: 40px;
    height: 40px;

    &--red {
      color: #ff525b;
      background: rgba(255, 65, 75, 0.2);
    }

    &--green {
      color: #24dc83;
      background: rgba(30, 210, 120, 0.2);
    }

    &--lime {
      color: #b1d94a;
      background: rgba(165, 200, 60, 0.2);
    }
  }

  .stat-title {
    font-size: 18px;
    color: #c5c6ce;
  }

  .stat-value {
    font-size: 46px;
  }
</style>
