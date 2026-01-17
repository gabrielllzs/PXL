<script setup>
import { ref } from 'vue'
import { useAuth } from '@/composables/useAuth'

const { isInGroup } = useAuth()



const emit = defineEmits(['update:modelValue'])

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    }
})

function close() {
    emit('update:modelValue', false)
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <div v-if="isInGroup()">
                <div class="modal-header">
                    <p>group</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
    pointer-events: auto;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 8px;
    width: 100%;
    max-width: 400px;
    position: relative;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-content h2 {
    margin: 0 0 16px 0;
    color: #1e1e1e;
    font-size: 22px;
}


</style>
