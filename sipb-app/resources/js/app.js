import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { router } from '@inertiajs/vue3';
import nprogress from 'nprogress';

nprogress.configure({ showSpinner: false });

router.on("start", () => nprogress.start());
router.on("finish", () => nprogress.done());

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);

        const loading = document.getElementById('sipb-loading');
        if (loading) {
            const img = loading.querySelector('img');
            if (img && !img.complete) {
                img.onload = () => loading.remove();
            } else {
                loading.remove();
            }
        }
    },
});
//
