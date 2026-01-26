<script setup>
import { ref, onMounted } from 'vue'

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    videoUrl: {
        type: String,
        default: 'https://www.youtube.com/watch?v=123'
    }
})

const emit = defineEmits(['update:modelValue'])

const STORAGE_KEY = 'pxl_welcome_dismissed'

onMounted(() => {
    // Check if user has previously dismissed the welcome modal
    const dismissed = localStorage.getItem(STORAGE_KEY)
    if (!dismissed) {
        emit('update:modelValue', true)
    }
})

function close() {
    // Automatically remember dismissal
    localStorage.setItem(STORAGE_KEY, 'true')
    emit('update:modelValue', false)
}

function openVideo() {
    window.open(props.videoUrl, '_blank', 'noopener,noreferrer')
}

function getYouTubeThumbnail(url) {
    const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/)
    return match ? `https://img.youtube.com/vi/${match[1]}/maxresdefault.jpg` : null
}
</script>

<template>
    <div v-if="modelValue" class="modal-overlay" @click="close">
        <div class="modal-content" @click.stop>
            <button class="modal-close" @click="close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="modal-header">
                <h2>Welcome to PXL!</h2>
                <p class="header-subtitle">
                    A collaborative pixel art canvas where you can create together with the community
                </p>
            </div>

            <div class="welcome-content">
                <div class="video-section" @click="openVideo">
                    <div class="video-thumbnail">
                        <img
                            v-if="getYouTubeThumbnail(videoUrl)"
                            :src="getYouTubeThumbnail(videoUrl)"
                            alt="Video thumbnail"
                            @error="$event.target.style.display='none'"
                        />
                        <div class="play-button">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.5 5.653c0-1.426 1.529-2.33 2.779-1.643l11.54 6.348c1.295.712 1.295 2.573 0 3.285L7.28 19.991c-1.25.687-2.779-.217-2.779-1.643V5.653z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    </div>
                    <span class="video-label">Watch Tutorial Video</span>
                </div>

                <div class="features-sections">
                    <div class="section-title">
                        <h3>As a Guest</h3>
                    </div>
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon cooldown">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>10-Second Cooldown</strong>
                                <span>Wait 10 seconds between pixel placements</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon palette">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M20.599 1.5c-.376 0-.743.111-1.055.32l-5.08 3.385a18.747 18.747 0 00-3.471 2.987 10.04 10.04 0 014.815 4.815 18.748 18.748 0 002.987-3.472l3.386-5.079A1.902 1.902 0 0020.599 1.5zm-8.3 14.025a18.76 18.76 0 001.896-1.207 8.026 8.026 0 00-4.513-4.513A18.75 18.75 0 008.475 11.7l-.278.5a5.26 5.26 0 013.601 3.602l.502-.278zM6.75 13.5A3.75 3.75 0 003 17.25a1.5 1.5 0 01-1.601 1.497.75.75 0 00-.7 1.123 5.25 5.25 0 009.8-2.62 3.75 3.75 0 00-3.75-3.75z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Fixed Palette Colors</strong>
                                <span>Use only predefined colors from the palette</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon captcha">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 01.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>hCaptcha Required</strong>
                                <span>Complete verification to place pixels</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon view">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/>
                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>View & Collaborate</strong>
                                <span>See the canvas and real-time updates</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-title">
                        <h3>When Logged In</h3>
                    </div>
                    <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon custom">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M20.599 1.5c-.376 0-.743.111-1.055.32l-5.08 3.385a18.747 18.747 0 00-3.471 2.987 10.04 10.04 0 014.815 4.815 18.748 18.748 0 002.987-3.472l3.386-5.079A1.902 1.902 0 0020.599 1.5zm-8.3 14.025a18.76 18.76 0 001.896-1.207 8.026 8.026 0 00-4.513-4.513A18.75 18.75 0 008.475 11.7l-.278.5a5.26 5.26 0 013.601 3.602l.502-.278zM6.75 13.5A3.75 3.75 0 003 17.25a1.5 1.5 0 01-1.601 1.497.75.75 0 00-.7 1.123 5.25 5.25 0 009.8-2.62 3.75 3.75 0 00-3.75-3.75z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Custom Colors</strong>
                                <span>Use any color code you want</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon level">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Level Up System</strong>
                                <span>Earn more pixels as you contribute</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon collaborate">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd"/>
                                    <path d="M5.082 14.254a8.287 8.287 0 00-1.308 5.135 9.687 9.687 0 01-1.764-.44l-.115-.04a.563.563 0 01-.373-.487l-.01-.121a3.75 3.75 0 013.57-4.047zM20.226 19.389a8.287 8.287 0 00-1.308-5.135 3.75 3.75 0 013.57 4.047l-.01.121a.563.563 0 01-.373.486l-.115.04c-.567.2-1.156.349-1.764.441z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Join Groups</strong>
                                <span>Collaborate with friends in groups</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon save">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Save Locations</strong>
                                <span>Bookmark favorite spots on the canvas</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon leaderboard">
                                <svg fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22,7H16.333V4a1,1,0,0,0-1-1H8.667a1,1,0,0,0-1,1v7H2a1,1,0,0,0-1,1v8a1,1,0,0,0,1,1H22a1,1,0,0,0,1-1V8A1,1,0,0,0,22,7ZM7.667,19H3V13H7.667Zm6.666,0H9.667V5h4.666ZM21,19H16.333V9H21Z"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Leaderboards</strong>
                                <span>Compete and see top contributors</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon wayback">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 4v6h6"/>
                                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>Wayback Feature</strong>
                                <span>View canvas history over time</span>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon no-captcha">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 01.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="feature-text">
                                <strong>No Captcha</strong>
                                <span>Skip verification after email confirmation</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn-primary" @click="close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path fill-rule="evenodd" d="M9.315 7.584C12.195 3.883 16.615 1.5 21.75 1.5a.75.75 0 01.75.75c0 5.056-2.383 9.555-6.084 12.436A6.75 6.75 0 019.75 22.5a.75.75 0 01-.75-.75v-4.131A15.838 15.838 0 016.382 15H2.25a.75.75 0 01-.75-.75 6.75 6.75 0 017.815-6.666zM15 6.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z" clip-rule="evenodd"/>
                    <path d="M5.26 17.242a.75.75 0 10-.897-1.203 5.243 5.243 0 00-2.05 5.022.75.75 0 00.625.627 5.243 5.243 0 005.022-2.051.75.75 0 10-1.202-.897 3.744 3.744 0 01-3.008 1.51c0-1.23.592-2.323 1.51-3.008z"/>
                </svg>
                Get Started
            </button>
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
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    pointer-events: auto;
}

.modal-content {
    background: #fafafa;
    border-radius: 20px;
    padding: 32px;
    max-width: 700px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    font-family: 'pixel art', monospace;
}

.modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(0, 0, 0, 0.05);
    border: none;
    color: #666;
    cursor: pointer;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    transition: all 0.2s;
    z-index: 10;
}

.modal-close:hover {
    background: rgba(0, 0, 0, 0.1);
    color: #1e1e1e;
}

.modal-close svg {
    width: 22px;
    height: 22px;
}

.modal-header {
    text-align: center;
    margin-bottom: 24px;
}

.header-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    color: white;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.header-icon svg {
    width: 28px;
    height: 28px;
}

.modal-header h2 {
    margin: 0 0 6px 0;
    color: #1e1e1e;
    font-size: 24px;
    font-weight: 700;
}

.header-subtitle {
    margin: 0;
    color: #888;
    font-size: 14px;
    line-height: 1.5;
}

.welcome-content {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 20px;
}

.video-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: transform 0.2s;
}

.video-section:hover {
    transform: scale(1.02);
}

.video-section:hover .play-button {
    transform: translate(-50%, -50%) scale(1.1);
    background: rgba(234, 88, 12, 1);
}

.video-thumbnail {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%);
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 64px;
    height: 64px;
    background: rgba(234, 88, 12, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    transition: all 0.2s;
    box-shadow: 0 4px 20px rgba(234, 88, 12, 0.4);
}

.play-button svg {
    width: 28px;
    height: 28px;
    margin-left: 4px;
}

.video-label {
    font-size: 14px;
    color: #ea580c;
    font-weight: 600;
}

.features-sections {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.section-title {
    margin: 0;
    padding: 0;
}

.section-title h3 {
    margin: 0 0 12px 0;
    color: #1e1e1e;
    font-size: 16px;
    font-weight: 700;
    padding-bottom: 8px;
    border-bottom: 2px solid rgba(0, 0, 0, 0.1);
}

.features-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.03);
    border-radius: 12px;
}

.feature-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.feature-icon.cooldown {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.feature-icon.palette {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.feature-icon.captcha {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.feature-icon.view {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.feature-icon.instant {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.feature-icon.custom {
    background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
}

.feature-icon.level {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.feature-icon.collaborate {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.feature-icon.save {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.feature-icon.leaderboard {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.feature-icon.wayback {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
}

.feature-icon.no-captcha {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.feature-icon svg {
    width: 20px;
    height: 20px;
}

.feature-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.feature-text strong {
    color: #1e1e1e;
    font-size: 14px;
}

.feature-text span {
    color: #888;
    font-size: 12px;
}

.btn-primary {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 14px 24px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(234, 88, 12, 0.3);
    box-shadow: 0 2px 12px rgba(234, 88, 12, 0.15);
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: 'pixel art', monospace;
    font-weight: 600;
    font-size: 15px;
    color: #ea580c;
}

.btn-primary:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 6px 20px rgba(234, 88, 12, 0.25);
    border-color: rgba(234, 88, 12, 0.5);
    background: rgba(255, 247, 237, 0.95);
}

.btn-primary:active {
    transform: translateY(0) scale(0.98);
}

.btn-primary svg {
    width: 20px;
    height: 20px;
}

@media (max-width: 640px) {
    .modal-content {
        width: 100%;
        height: 100%;
        border-radius: 18px;
        display: flex;
        flex-direction: column;
        padding: 24px 40px;
    }

    .modal-close {
        top: 20px;
        right: 20px;
        width: 44px;
        height: 44px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 12px;
    }

    .welcome-content {
        flex: 1;
    }

    .play-button {
        width: 56px;
        height: 56px;
    }

    .play-button svg {
        width: 24px;
        height: 24px;
    }
}
</style>
