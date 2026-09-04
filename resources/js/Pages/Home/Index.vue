<script setup>

import { defineProps } from 'vue'
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps(['auth', 'flash'])

</script>

<template>
    <Head title="Main page" />
    <div>
        <h1>Главная страница</h1>
        <div>
            <span v-if="auth.user">Добро пожаловать, {{ auth.user.name }}</span>
            <div v-if="flash">{{ flash }}</div>
        </div>
    </div>
</template>