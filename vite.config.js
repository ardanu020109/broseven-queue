import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/admin.css",
                "resources/css/public.css",
                "resources/css/display.css",
                "resources/css/print.css",
                "resources/css/auth.css",
                "resources/js/app.js",
                "resources/js/ui.js",
                "resources/js/queue.js",
                "resources/js/display.js",
                'resources/js/admin.js',
                'resources/js/public.js',
                'resources/js/dashboard.js',
                'resources/js/queue-item-form.js',
                'resources/js/queue-manage.js',
                'resources/js/color-settings.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ["**/storage/framework/views/**"],
        },
    },
});
