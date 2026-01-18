<template>
  <div class="gallery-grid">
    <!-- Sort Controls -->
    <v-row class="mb-6">
      <v-col cols="12" class="d-flex justify-center">
        <v-btn-toggle
          :model-value="sortBy"
          @update:model-value="$emit('update:sortBy', $event)"
          color="primary"
          mandatory
          variant="outlined"
          divided
          class="sort-toggle"
        >
          <v-btn value="recent" class="sort-btn">
            <v-icon start>mdi-clock-outline</v-icon>
            Recent
          </v-btn>
          <v-btn value="popular" class="sort-btn">
            <v-icon start>mdi-fire</v-icon>
            Popular
          </v-btn>
        </v-btn-toggle>
      </v-col>
    </v-row>

    <!-- Loading State -->
    <LoadingSpinner 
      v-if="loading" 
      :message="loadingMessage"
    />

    <!-- Drawings Grid -->
    <v-row v-else-if="drawings.length > 0">
      <v-col
        v-for="drawing in drawings"
        :key="drawing.id"
        cols="12"
        sm="6"
        md="4"
        lg="3"
      >
        <DrawingCard 
          :drawing="drawing"
          @toggle-vote="$emit('toggle-vote', $event)"
          @view="$emit('view', $event)"
        />
      </v-col>
    </v-row>

    <!-- Empty State -->
    <EmptyState
      v-else
      icon="mdi-draw"
      :title="emptyTitle"
      :message="emptyMessage"
      :action-text="emptyActionText"
      :action-icon="emptyActionIcon"
      @action="$emit('empty-action')"
    />
  </div>
</template>

<script setup>
import DrawingCard from '../DrawingCard'
import LoadingSpinner from '../LoadingSpinner'
import EmptyState from '../EmptyState'

defineProps({
  drawings: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  loadingMessage: {
    type: String,
    default: 'Loading drawings...'
  },
  sortBy: {
    type: String,
    default: 'recent'
  },
  emptyTitle: {
    type: String,
    default: 'No drawings yet'
  },
  emptyMessage: {
    type: String,
    default: 'Be the first to share your art!'
  },
  emptyActionText: {
    type: String,
    default: 'Create Drawing'
  },
  emptyActionIcon: {
    type: String,
    default: 'mdi-draw'
  }
})

defineEmits(['update:sortBy', 'toggle-vote', 'view', 'empty-action'])
</script>

<style scoped src="./GalleryGrid.css"></style>
