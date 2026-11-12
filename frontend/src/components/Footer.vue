<template>
  <div class="text-center text-body-medium">
    <div>Source: OpenExchangeRates</div>
    <div>Exchange rates updates every monday at 09:00 (UTC+3)</div>
    <div>Last update: {{ formattedDate }}</div>
    <p>
      Made with
      <v-icon icon="fas fa-heart" color="red" size="18" />
      by Anton Averkiev
    </p>
    <p>
      <a href="https://github.com/wowgamr/spotify-pricing" target="_blank">
        <v-icon icon="fab fa-github" color="white" size="18" />
      </a>
    </p>
    <p class="d-flex ga-2 justify-center mt-4">
      <span class="fi fi-ua" />
      <v-icon icon="fas fa-dove" color="white" size="18" />
      <span class="fi fi-ru" />
    </p>
  </div>
</template>

<script setup lang="ts">
  import { computed } from 'vue'

  const props = defineProps<{
    lastUpdated: Date | null
  }>()

  const formattedDate = computed(() => {
    return props.lastUpdated?.toLocaleString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  })
</script>
