<template>
  <v-container class="d-flex flex-column ga-3">
    <h1 class="headline font-weight-light text-center">Spotify Premium Index</h1>
    <MapChart :summary="pricesData" />
    <StatsBlock :summary="pricesData" />
    <TableChart :summary="pricesData" />
    <Footer :last-updated="lastUpdated" />
  </v-container>
</template>

<script setup lang="ts">
  import Footer from '@/components/Footer.vue'
  import MapChart from '@/components/MapChart.vue'
  import StatsBlock from '@/components/StatsBlock.vue'
  import TableChart from '@/components/TableChart.vue'
  import { ref } from 'vue'

  const pricesData = ref<TSummaryItem[]>([])
  const lastUpdated = ref<Date | null>(null)

  const getData = async () => {
    const response = await fetch('/data/summary.json')
    pricesData.value = await response.json()

    const lastModified = response.headers.get('Last-Modified')
    lastUpdated.value = lastModified ? new Date(lastModified) : null
  }

  getData()
</script>

<style lang="scss">
  .v-icon.fas,
  .v-icon.fab {
    font-size: 1em;
  }
</style>

<style lang="scss" scoped>
  .headline {
    font-size: 3rem;
  }
</style>