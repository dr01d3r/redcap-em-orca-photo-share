<script setup>
import {computed, onBeforeMount, ref} from "vue";
import {isEmpty, isNotEmpty} from '@primeuix/utils/object';
import { DateTime, Interval } from 'luxon';
import ModuleUtils from '../ModuleUtils';

const debug = ref();
const config = ref({});
const isInvalid = ref(false);
const isLoading = ref(false);

const refreshTokenExpiresHours = computed(() => {
    if (isNotEmpty(config.value) && isNotEmpty(config.value['refresh_token_expires'])) {
        // DateTime.fromSQL is able to parse both date and datetime fields
        // https://moment.github.io/luxon/#/parsing?id=sql
        let d1 = DateTime.now();
        let d2 = DateTime.fromSQL(config.value['refresh_token_expires']);
        // calculate the diff with min/max, if set
        return Interval.fromDateTimes(d1, d2).length("hours");
    }
    return null;
});
const refreshTokenExpiresDisplay = computed(() => {
    return `${config.value['refresh_token_expires']} (${ModuleUtils.hoursToHuman(refreshTokenExpiresHours.value)})`;
});

const init = () => {
    isLoading.value = true;
    OrcaGooglePhotoShare().jsmo.ajax('initialize-main-dashboard', {})
        .then(function(response) {
            config.value = response.config;
            isInvalid.value = response['is-valid'] !== true;
        })
        .catch(function(err) {
            debug.value = err;
        })
        .finally(() => {
            isLoading.value = false;
        });
}

const doRunner = () => {
    isLoading.value = true;
    OrcaGooglePhotoShare().jsmo.ajax('do-api-runner', {})
        .then(function(response) {
            debug.value = response;
        })
        .catch(function(err) {
            debug.value = err;
        })
        .finally(() => {
            isLoading.value = false;
        });
}

onBeforeMount(() => {
    init();
});
</script>

<template>
    <div class="projhdr">
        <i class="fa-solid fa-gears">&ZeroWidthSpace;</i>&nbsp;Orca Photo Share Cron Runner
    </div>
    <Toast />
    <BlockUI :blocked="isLoading">
        <div class="card">
            <div class="card-header">
                <div class="row">
                    <div class="col-12">
                        <label class="fw-bold text-decoration-underline mb-0">Description</label>
                        <div class="lead mt-2">This runner allows you to manually run the API push, to send any approved images to the Photos Album.</div>
                    </div>
                </div>
                <div v-if="isInvalid" class="alert alert-danger mt-3">
                    <div class="fs-5 fw-bold">Configuration Invalid!</div>
                    <hr class="my-2" />
                    <div class="fs-6">Unable to execute the API Runner as the configuration is incomplete. Please refer back to the Configuration page to complete the set up.</div>
                </div>
                <div v-else-if="isNotEmpty(refreshTokenExpiresHours) && refreshTokenExpiresHours <= 24" class="alert alert-danger fs-6 mt-3">
                    <div class="fw-bold fs-5">Refresh Token about to Expire!</div>
                    <hr class="my-2" />
                    <div><strong>Expires:</strong> {{ refreshTokenExpiresDisplay }}</div>
                    <div class="mt-2">Your integration will cease to function after the token has expired! Please re-authorize the application on the configuration page as soon as possible.</div>
                </div>
                <div v-else-if="isNotEmpty(refreshTokenExpiresHours) && refreshTokenExpiresHours <= 48" class="alert alert-warning fs-6 mt-3">
                    <div class="fw-bold fs-5">Refresh Token Expiring Soon!</div>
                    <hr class="my-2" />
                    <div><strong>Expires:</strong> {{ refreshTokenExpiresDisplay }}</div>
                    <div class="mt-2">Please consider re-authorizing the application on the configuration page.</div>
                </div>
                <div v-else class="text-muted fst-italic">
                    <hr class="my-1" />
                    <strong>Refresh Token Expires:</strong> {{ refreshTokenExpiresDisplay }}
                </div>
            </div>
            <div class="card-body">
                <button class="btn btn-danger" tabindex="-1" @click="doRunner" :disabled="isInvalid">Execute</button>
            </div>
        </div>
        <pre v-if="debug" class="mt-3">{{ debug }}</pre>
        <ProgressSpinner v-show="isLoading" class="overlay"/>
    </BlockUI>
</template>

<style>
.some-fake-class {
    display: none;
}
</style>