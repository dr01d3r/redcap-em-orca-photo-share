import { createApp } from "vue";
import App from "./App.vue";

import PrimeVue from "primevue/config";
import Lara from '@primevue/themes/lara';

import ProgressSpinner from 'primevue/progressspinner';
import BlockUI from 'primevue/blockui';
import Dialog from 'primevue/dialog';
import Tooltip from 'primevue/tooltip';

import Toast from 'primevue/toast';
import ToastService from 'primevue/toastservice';

const app = createApp(App);

app.use(ToastService);
app.use(PrimeVue, {
    theme: {
        preset: Lara
    }
});

app.directive('tooltip', Tooltip);
// make v-focus usable in all components
app.directive('focus', {
    mounted: (el) => el.focus()
})

app.component("BlockUI", BlockUI);
app.component("ProgressSpinner", ProgressSpinner);
app.component("Dialog", Dialog);
app.component("Toast", Toast);

app.mount("#GOOGLE_PHOTOS_CONFIG");