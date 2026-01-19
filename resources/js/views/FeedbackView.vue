<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from '@/composables/useToast'
import { useAuth } from '@/composables/useAuth'
import ToastContainer from '@/components/ToastContainer.vue'

const { showToast } = useToast()
const { user, checkAuth } = useAuth()
const email = ref('')
const username = ref('')
const message = ref('')
const type = ref('suggestion')
const loading = ref(false)

onMounted(async () => {
    await checkAuth()
    if (user.value) {
        email.value = user.value.email || ''
        username.value = user.value.username || ''
    }
})

async function handleSubmit() {
    if (!message.value.trim()) return

    loading.value = true
    try {
        await axios.post('/feedback', {
            email: email.value,
            username: username.value,
            message: message.value,
            type: type.value
        })
        showToast(`${type.value === 'bug' ? 'Bug report' : 'Suggestion'} submitted! Thank you.`, 'success')
        message.value = ''
        setTimeout(() => {
            window.location.href = '/'
        }, 1500)
    } catch {
        showToast(`Failed to submit ${type.value}`, 'error')
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <div>
        <ToastContainer />
        <div class="container">
            <div class="form-container">
                <h1>Feedback</h1>
                <form @submit.prevent="handleSubmit">
                    <div class="form-group">
                        <label>Email</label>
                        <input
                            v-model="email"
                            type="email"
                            required
                            placeholder="your@email.com"
                        />
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input
                            v-model="username"
                            type="text"
                            placeholder="Your username (optional)"
                        />
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <div class="type-selector">
                            <button
                                type="button"
                                class="type-btn"
                                :class="{ active: type === 'suggestion' }"
                                @click="type = 'suggestion'"
                            >
                                Suggestion
                            </button>
                            <button
                                type="button"
                                class="type-btn"
                                :class="{ active: type === 'bug' }"
                                @click="type = 'bug'"
                            >
                                Bug Report
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ type === 'bug' ? 'Bug Description' : 'Your Suggestion' }}</label>
                        <textarea
                            v-model="message"
                            required
                            :placeholder="type === 'bug' ? 'Describe the bug you encountered...' : 'Share your ideas and suggestions...'"
                            rows="8"
                        ></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary" :disabled="loading || !message.trim()">
                            {{ loading ? 'Submitting...' : 'Submit' }}
                        </button>
                        <a href="/" class="btn-secondary">Back to Canvas</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #f9f9f9;
}

.form-container {
    background: white;
    border-radius: 12px;
    padding: 40px;
    max-width: 600px;
    width: 100%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form-container h1 {
    margin: 0 0 24px 0;
    color: #1e1e1e;
    font-size: 28px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #1e1e1e;
    font-weight: 500;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
}

.type-selector {
    display: flex;
    gap: 12px;
}

.type-btn {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    background: white;
    color: #666;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
}

.type-btn:hover {
    border-color: #2563eb;
    color: #2563eb;
}

.type-btn.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.form-actions {
    display: flex;
    gap: 12px;
}

.btn-primary {
    background: #2563eb;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
}

.btn-primary:hover:not(:disabled) {
    background: #3b82f6;
    transform: translateY(-1px);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #e5e7eb;
    color: #1e1e1e;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-secondary:hover {
    background: #d1d5db;
}
</style>
