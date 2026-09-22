<script setup>

import { defineProps } from 'vue'
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const {auth, flash, user} = defineProps({
    'auth': {
        required: true,
        type: Object
    },
    'flash': {
        required: true,
        type: Object
    },
    'user': {
        required: true,
        type: Object
    }
})

</script>

<template>
    <AppLayout></AppLayout>
</template>