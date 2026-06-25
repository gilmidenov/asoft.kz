<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import axios from 'axios'

// ── Данные ──────────────────────────────────────────────────────
const banners = ref([])
const current = ref(0)
let   timer   = null

// Базовый корпоративный слайд — всегда первый в ротации
const fallback = {
    title:       'Лицензионное программное обеспечение для бизнеса',
    subtitle:    'Официальные лицензии Microsoft, Kaspersky, Adobe и других ведущих вендоров. Мгновенная доставка ключей активации.',
    button_text: 'Перейти в каталог',
    button_url:  '/catalog',
    image:       null,
}

// Фолбэк всегда первый — пользовательские баннеры добавляются после.
// При 0 баннеров slides = [fallback] (нет стрелок — 1 слайд).
// При 1+ баннере slides = [fallback, banner1, ...] (стрелки появляются).
const slides = computed(() => [fallback, ...banners.value])

// ── Автопрокрутка ────────────────────────────────────────────────
function startTimer() {
    if (slides.value.length < 2) return
    timer = setInterval(() => {
        current.value = (current.value + 1) % slides.value.length
    }, 5000)
}

function stopTimer() { clearInterval(timer) }

function goTo(idx) {
    current.value = idx
    stopTimer()
    startTimer()
}

function prev() { goTo((current.value - 1 + slides.value.length) % slides.value.length) }
function next() { goTo((current.value + 1) % slides.value.length) }

// ── Загрузка ────────────────────────────────────────────────────
onMounted(async () => {
    try {
        const { data } = await axios.get('/banners')
        banners.value = data
    } catch {
        // используем только фолбэк
    }
    startTimer()
})

onUnmounted(stopTimer)
</script>

<template>
    <section
        class="relative overflow-hidden"
        style="min-height: 440px;"
        @mouseenter="stopTimer"
        @mouseleave="startTimer"
    >
        <!--
            Все слайды всегда в DOM, перекрываются через absolute inset-0.
            Видимость управляется opacity + z-index (не v-show / display:none).
            transition-opacity duration-700 даёт плавное кроссфейд-переключение.
        -->
        <div
            v-for="(slide, idx) in slides"
            :key="idx"
            :class="[
                'absolute inset-0 flex items-center justify-center transition-opacity duration-700',
                idx === current
                    ? 'opacity-100 z-10'
                    : 'opacity-0 z-0 pointer-events-none'
            ]"
        >
            <!-- Фон: изображение с затемнением или градиент -->
            <div
                v-if="slide.image"
                class="absolute inset-0 bg-cover bg-center"
                :style="{ backgroundImage: `url(${slide.image})` }"
            >
                <div class="absolute inset-0 bg-black/50" />
            </div>
            <div v-else class="absolute inset-0 bg-gradient-to-br from-header via-slate-800 to-primary-900" />

            <!-- Контент слайда -->
            <div class="relative z-10 text-white text-center px-4 py-20 w-full max-w-4xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-bold mb-5 leading-tight">
                    {{ slide.title }}
                </h1>
                <p v-if="slide.subtitle" class="text-gray-300 text-lg mb-10 max-w-2xl mx-auto">
                    {{ slide.subtitle }}
                </p>
                <div class="flex gap-4 justify-center flex-wrap">
                    <component
                        :is="slide.button_url?.startsWith('/') ? RouterLink : 'a'"
                        v-if="slide.button_text && slide.button_url"
                        :to="slide.button_url?.startsWith('/') ? slide.button_url : undefined"
                        :href="!slide.button_url?.startsWith('/') ? slide.button_url : undefined"
                        class="bg-primary hover:bg-primary-700 text-white font-semibold px-8 py-3 rounded-xl transition-colors"
                    >
                        {{ slide.button_text }}
                    </component>
                    <a href="tel:+77075973777"
                        class="border border-gray-500 hover:border-white text-white font-semibold px-8 py-3 rounded-xl transition-colors">
                        Позвонить нам
                    </a>
                </div>
            </div>
        </div>

        <!-- Стрелки и точки — z-20 чтобы быть поверх всех слайдов -->
        <template v-if="slides.length > 1">
            <button
                @click="prev"
                class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/30 hover:bg-black/60 text-white flex items-center justify-center transition-colors"
                aria-label="Предыдущий"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button
                @click="next"
                class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-black/30 hover:bg-black/60 text-white flex items-center justify-center transition-colors"
                aria-label="Следующий"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <!-- Индикаторные точки -->
            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex gap-2">
                <button
                    v-for="(_, idx) in slides"
                    :key="idx"
                    @click="goTo(idx)"
                    :class="[
                        'w-2.5 h-2.5 rounded-full transition-all duration-300',
                        idx === current ? 'bg-white scale-125' : 'bg-white/40 hover:bg-white/70'
                    ]"
                    :aria-label="`Перейти к слайду ${idx + 1}`"
                />
            </div>
        </template>
    </section>
</template>
