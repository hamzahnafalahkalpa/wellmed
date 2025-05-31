<script setup lang="ts">
import { Label } from '@klinik/components/ui/label'

interface Props {
    label ?: string;    
    id ?: string;
    type ?: string;
}

const props = withDefaults(defineProps<Props>(), {
    label : 'Label',
    type : 'text'
})
</script>

<template>
    <div class="w-full gap-1 flex font-bold">
        <Label class="my-auto" :for="id">{{label}}</Label>
        <span class="my-auto">:</span>
        <slot/>
    </div>
</template>