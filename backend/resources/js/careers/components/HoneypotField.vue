<script setup lang="ts">
defineProps<{
  modelValue: string
}>()

defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()
</script>

<template>
  <!-- Hidden bot trap — must remain empty for legitimate submissions -->
  <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px; height: 0; width: 0; overflow: hidden;">
    <label for="website">Website</label>
    <input
      id="website"
      type="text"
      name="website"
      tabindex="-1"
      autocomplete="off"
      :value="modelValue"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
  </div>
</template>
