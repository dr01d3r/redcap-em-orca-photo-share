<script setup>
import { onBeforeMount, ref, computed } from "vue";
import { useToast } from 'primevue/usetoast';
import {isEmpty} from '@primeuix/utils/object';

const debug = ref();
const config = ref({});
const isLoading = ref(false);
</script>
<template>
<h1>Hello, Index Vue!!</h1>
</template>

<style>
.some-fake-class {
    display: none;
}
</style>