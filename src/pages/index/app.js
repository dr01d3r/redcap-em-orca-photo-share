import { createApp } from "vue";
import App from "./App.vue";

import PrimeVue from "primevue/config";
import Lara from '@primevue/themes/lara';

import ProgressSpinner from 'primevue/progressspinner';
import BlockUI from 'primevue/blockui';
import Tooltip from 'primevue/tooltip';

const app = createApp(App);

app.use(PrimeVue, {
    theme: {
        preset: Lara
    }
});

app.directive('tooltip', Tooltip);

app.component("BlockUI", BlockUI);
app.component("ProgressSpinner", ProgressSpinner);

app.mount("#GOOGLE_PHOTOS_INDEX");