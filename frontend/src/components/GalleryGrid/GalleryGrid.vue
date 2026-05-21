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

<style scoped>
/* Gallery Grid - Doodle Style */
.gallery-grid {
  width: 100%;
}

.sort-toggle {
  border: 4px dashed #9370DB !important;
  border-radius: 50px !important;
  overflow: hidden;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
  box-shadow: 
    5px 5px 0 #FFD700,
    10px 10px 0 #FF1493,
    0 0 20px rgba(255, 105, 180, 0.3);
  transform: rotate(-0.5deg);
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.sort-toggle:hover {
  transform: rotate(0.5deg) scale(1.02);
  box-shadow: 
    6px 6px 0 #FFD700,
    12px 12px 0 #FF1493,
    0 0 30px rgba(255, 105, 180, 0.5);
}

.sort-btn {
  font-family: 'Nunito', 'Segoe UI', system-ui, sans-serif;
  font-weight: 800 !important;
  color: #E6B3FF !important;
  text-transform: none !important;
  transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
  font-size: 1.1rem !important;
  padding: 1rem 1.5rem !important;
  position: relative;
}

.sort-btn:hover {
  background: linear-gradient(135deg, #2d2d44 0%, #1e1e3f 100%) !important;
  transform: scale(1.08);
  color: #FFB3E6 !important;
}

.sort-btn.active {
  background: linear-gradient(135deg, #FF1493 0%, #9370DB 100%) !important;
  color: white !important;
  transform: scale(1.1);
  box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.3);
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.sort-btn.active::after {
  content: '\2728';
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  animation: twinkle 1.5s ease-in-out infinite;
}

@keyframes twinkle {
  0%, 100% {
    opacity: 0.5;
    transform: translateY(-50%) scale(1);
  }
  50% {
    opacity: 1;
    transform: translateY(-50%) scale(1.3);
  }
}
</style>
