<template>
  <v-chart v-if="formattedCountries.length && mdAndUp" :option="mapOptions" class="mx-auto map" />
</template>

<script setup lang="ts">
  import { registerMap, use } from 'echarts/core'
  import { CanvasRenderer } from 'echarts/renderers'
  import { MapChart } from 'echarts/charts'
  import { TitleComponent, TooltipComponent, VisualMapComponent } from 'echarts/components'
  import VChart from 'vue-echarts'
  import { computed } from 'vue'
  import type { TopLevelFormatterParams } from 'echarts/types/dist/shared.js'
  import world from '@/assets/custom.geo.json'
import { useDisplay } from 'vuetify'

  use([MapChart, CanvasRenderer, TitleComponent, TooltipComponent, VisualMapComponent])
  registerMap('world', world as any)

  const props = defineProps<{
    summary: TSummaryItem[]
  }>()

  const { mdAndUp } = useDisplay()

  const minPrice = computed(() => Math.min(...formattedCountries.value.map((el) => el.value)) || 0)
  const maxPrice = computed(() => Math.max(...formattedCountries.value.map((el) => el.value)) || 0)

  const formattedCountries = computed<TCountriesData[]>(() => {
    return props.summary?.map((el) => ({
      name: el.title,
      id: el.countryCode,
      currency: el.currency,
      originalPrice: el.price,
      value: el.convertedPrice,
    }))
  })

  const mapOptions = computed<echarts.EChartsOption>(() => ({
    tooltip: {
      trigger: 'item',
      formatter(params: TopLevelFormatterParams) {
        if (!('data' in params && params.data)) {
          return 'name' in params && params.name ? `<strong>${params.name}</strong>` : ''
        }

        const data = params.data as TCountriesData
        const title = `<strong>${data.name}</strong>`

        if (!data.originalPrice) return title

        const originalPrice = data.currency !== 'USD' ? `<br>${data.originalPrice} ${data.currency}` : ''
        return `${title}<br>${data.value} USD${originalPrice}`
      },
    },
    visualMap: {
      min: minPrice.value,
      max: maxPrice.value,
      left: 'left',
      top: 'center',
      text: ['High', 'Low'],
      textStyle: {
        color: '#7e7e7e',
      },
      calculable: false,
      inRange: {
        color: ['#b4e262', '#a0da37', '#85bb23', '#66901b', '#486513'],
      },
    },
    series: [
      {
        name: 'Price in USD',
        id: 'Individual Price',
        selectedMode: false,
        emphasis: {
          label: {
            show: false,
          },
        },
        type: 'map',
        map: 'world',
        itemStyle: {
          areaColor: '#7e7e7e',
        },
        roam: false,
        data: formattedCountries.value,
      },
    ],
  }))
</script>

<style lang="scss" scoped>
  .map {
    width: 95%;
    height: 80vh;
    max-height: 600px;
  }
</style>
