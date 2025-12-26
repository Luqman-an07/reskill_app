<script setup>
import { computed } from 'vue';

const props = defineProps({
    image: String, // URL gambar (jika ada)
    title: String, // Judul kursus (untuk inisial/alt)
    id: Number,    // ID kursus (untuk menentukan warna acak)
    className: String // Tambahan class css
});

// 1. Daftar Warna Gradient Keren (Tailwind)
const gradients = [
    'bg-gradient-to-br from-blue-500 to-cyan-400',
    'bg-gradient-to-br from-purple-500 to-pink-400',
    'bg-gradient-to-br from-emerald-500 to-teal-400',
    'bg-gradient-to-br from-orange-400 to-amber-400',
    'bg-gradient-to-br from-indigo-500 to-purple-500',
    'bg-gradient-to-br from-rose-500 to-red-400',
    'bg-gradient-to-br from-cyan-500 to-blue-500',
    'bg-gradient-to-br from-lime-500 to-green-400',
];

// 2. Pilih warna berdasarkan ID (Deterministik)
// ID 1 akan selalu dapat warna index 1, ID 2 warna index 2, dst.
const activeGradient = computed(() => {
    if (!props.id) return gradients[0];
    return gradients[props.id % gradients.length];
});

// 3. Ambil Inisial Judul (Misal: "Advanced React" -> "AR")
const initials = computed(() => {
    if (!props.title) return 'C';
    const words = props.title.split(' ');
    if (words.length > 1) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }
    return props.title.substring(0, 2).toUpperCase();
});
</script>

<template>
    <div 
        class="w-full h-full flex items-center justify-center overflow-hidden relative"
        :class="[
            className,
            !image ? activeGradient : 'bg-gray-200' // Jika tidak ada gambar, pakai gradient
        ]"
    >
        <img 
            v-if="image" 
            :src="'/storage/' + image" 
            :alt="title"
            class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
        />

        <div v-else class="text-white flex flex-col items-center justify-center w-full h-full relative">
            
            <div class="absolute top-0 right-0 -mr-8 -mt-8 w-24 h-24 rounded-full bg-white opacity-10"></div>
            <div class="absolute bottom-0 left-0 -ml-8 -mb-8 w-20 h-20 rounded-full bg-white opacity-10"></div>

            <span class="text-3xl font-black tracking-wider drop-shadow-md opacity-90">
                {{ initials }}
            </span>
            
            <span class="text-xs uppercase tracking-widest font-bold mt-1 opacity-75">Course</span>
        </div>
    </div>
</template>