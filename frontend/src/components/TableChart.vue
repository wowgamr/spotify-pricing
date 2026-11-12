<template>
  <div class="d-flex flex-wrap ga-4 justify-space-between mb-4">
    <v-text-field
      v-model.trim="search"
      density="comfortable"
      variant="outlined"
      prepend-inner-icon="fas fa-search"
      label="Search by country"
      class="max-w-350 w-100"
      clearable
      hide-details
    />
    <v-btn-toggle v-if="mdAndUp" v-model="region" :disabled="!!search" mandatory>
      <v-btn value="" class="">{{ mdAndUp ? 'All regions' : 'All' }}</v-btn>
      <v-btn value="Europe">
        <v-icon icon="fas fa-earth-europe" class="mr-2" size="16" />
        Europe
      </v-btn>
      <v-btn value="Americas">
        <v-icon icon="fas fa-earth-americas" class="mr-2" size="16" />
        Americas
      </v-btn>
      <v-btn value="Africa">
        <v-icon icon="fas fa-earth-africa" class="mr-2" size="16" />
        Africa
      </v-btn>
      <v-btn value="Asia">
        <v-icon icon="fas fa-earth-asia" class="mr-2" size="16" />
        Asia
      </v-btn>
      <v-btn value="Oceania">
        <v-icon icon="fas fa-earth-oceania" class="mr-2" size="16" />
        Oceania
      </v-btn>
    </v-btn-toggle>
    <v-select
      v-else
      v-model="region"
      :items="regionItems"
      :disabled="!!search"
      density="comfortable"
      variant="outlined"
      class="max-w-350 w-100"
      hide-details
    />
  </div>
  <v-data-table :headers="headers" :items="filteredSummary" items-per-page="-1" class="mb-8" hide-default-footer>
    <template #item.title="{ item }">
      <span :class="`fi fi-${item.rel === 'UK' ? 'gb' : item.rel.toLowerCase()} mr-1`" />
      {{ item.title }}
    </template>
    <template #item.convertedPrice="{ item }">
      <div class="d-flex ga-2 align-center">
        <div>
          ${{ item.convertedPrice }}
          <span v-if="item.currency !== 'USD'" class="text-label-small text-no-wrap opacity-50">
            ({{ item.price }} {{ item.currency }})
          </span>
        </div>
        <v-tooltip :content-props="{ class: '' }">
          <template #activator="{ props }">
            <v-icon
              v-if="item.restoredPrice"
              v-bind="props"
              icon="fas fa-circle-exclamation"
              size="16"
              color="red"
              class="cursor-pointer"
            />
          </template>
          <div class="max-w-300">
            Unable to update price from Spotify, so last known was used. The dollar-converted price has been updated
            based on it.
          </div>
        </v-tooltip>
      </div>
    </template>
    <template #item.comparison="{ item }">
      <div v-if="item.rel !== 'US'" class="d-flex ga-1 align-center">
        {{ comparePrice(item.convertedPrice) }}%
        <v-icon v-bind="getCompareParams(item)" size="10" />
      </div>
    </template>
  </v-data-table>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'
  import { useDisplay } from 'vuetify'

  const regionItems = [
    { title: 'All regions', value: '' },
    { title: 'Europe', value: 'Europe' },
    { title: 'Americas', value: 'Americas' },
    { title: 'Africa', value: 'Africa' },
    { title: 'Asia', value: 'Asia' },
    { title: 'Oceania', value: 'Oceania' },
  ]

  const props = defineProps<{
    summary: TSummaryItem[]
  }>()

  const { mdAndUp } = useDisplay()

  const search = ref('')
  const region = ref('')

  const usRegion = computed(() => props.summary?.find((el) => el.rel === 'US'))

  const headers = computed(() => ([
    { title: 'Country', key: 'title' },
    { title: 'Region', key: 'region' },
    { title: 'Price', key: 'convertedPrice' },
    ...(mdAndUp.value ? [{ title: 'vs. US price', key: 'comparison', value: (item: TSummaryItem) => comparePrice(item.convertedPrice) }] : []),
  ]))

  const filteredSummary = computed(() => {
    if (region.value && !search.value) return props.summary?.filter((el) => el.region === region.value)
    if (search.value) return props.summary?.filter((el) => el.title.toLowerCase().includes(search.value.toLowerCase()))
    return props.summary
  })

  const comparePrice = (price: number) => {
    const usPrice = usRegion.value?.price || 0
    return Math.round(((price - usPrice) / usPrice) * 100)
  }

  const getCompareParams = (item: TSummaryItem) => {
    const percent = comparePrice(item.convertedPrice)
    if (percent > 0) return { icon: 'fas fa-arrow-up', color: 'red' }
    return { icon: 'fas fa-arrow-down', color: 'green' }
  }
</script>

<style lang="scss" scoped>
  :deep(.v-data-table-header__sort-icon) {
    font-size: 12px;
  }

  .max-w-350 {
    max-width: 350px;
  }

  .max-w-300 {
    max-width: 300px;
  }
</style>
