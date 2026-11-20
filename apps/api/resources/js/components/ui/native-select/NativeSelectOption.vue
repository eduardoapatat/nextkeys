<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed, inject, useAttrs } from "vue"
import { cn } from "@/lib/utils"
import { isSelectedValue, nativeSelectValueKey } from "./context"

const props = defineProps<{ class?: HTMLAttributes["class"] }>()

const attrs = useAttrs()
const selectedValue = inject(nativeSelectValueKey, undefined)
const selected = computed(() => isSelectedValue(selectedValue?.value, attrs.value))
</script>

<template>
  <option data-slot="native-select-option" :selected="selected" :class="cn('bg-popover text-popover-foreground', props.class)">
    <slot />
  </option>
</template>
