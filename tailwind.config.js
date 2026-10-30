import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Instrument Sans"', ...defaultTheme.fontFamily.sans],
            },

            /*
             * Semantic palette mirrored from rules/design.md section 3.
             * Values are plain rgb triples wrapped in <alpha-value> so opacity
             * modifiers keep working; radii, shadows and layout live in
             * resources/css/app.css as CSS variables.
             */
            colors: {
                canvas: 'rgb(241 245 249 / <alpha-value>)',
                surface: 'rgb(255 255 255 / <alpha-value>)',
                'surface-muted': 'rgb(248 250 252 / <alpha-value>)',
                border: 'rgb(226 232 240 / <alpha-value>)',
                'border-strong': 'rgb(203 213 225 / <alpha-value>)',
                text: 'rgb(15 23 42 / <alpha-value>)',
                'text-secondary': 'rgb(51 65 85 / <alpha-value>)',
                'text-muted': 'rgb(100 116 139 / <alpha-value>)',

                primary: {
                    50: 'rgb(239 246 255 / <alpha-value>)',
                    100: 'rgb(219 234 254 / <alpha-value>)',
                    200: 'rgb(191 219 254 / <alpha-value>)',
                    300: 'rgb(147 197 253 / <alpha-value>)',
                    400: 'rgb(96 165 250 / <alpha-value>)',
                    500: 'rgb(59 130 246 / <alpha-value>)',
                    600: 'rgb(37 99 235 / <alpha-value>)',
                    700: 'rgb(29 78 216 / <alpha-value>)',
                    800: 'rgb(30 64 175 / <alpha-value>)',
                    900: 'rgb(30 58 138 / <alpha-value>)',
                    950: 'rgb(23 37 84 / <alpha-value>)',
                },

                success: {
                    50: 'rgb(236 253 245 / <alpha-value>)',
                    100: 'rgb(209 250 229 / <alpha-value>)',
                    600: 'rgb(5 150 105 / <alpha-value>)',
                    700: 'rgb(4 120 87 / <alpha-value>)',
                },

                warning: {
                    50: 'rgb(255 251 235 / <alpha-value>)',
                    100: 'rgb(254 243 199 / <alpha-value>)',
                    600: 'rgb(217 119 6 / <alpha-value>)',
                    700: 'rgb(180 83 9 / <alpha-value>)',
                },

                danger: {
                    50: 'rgb(254 242 242 / <alpha-value>)',
                    100: 'rgb(254 226 226 / <alpha-value>)',
                    600: 'rgb(220 38 38 / <alpha-value>)',
                    700: 'rgb(185 28 28 / <alpha-value>)',
                },

                info: {
                    50: 'rgb(240 249 255 / <alpha-value>)',
                    100: 'rgb(224 242 254 / <alpha-value>)',
                    600: 'rgb(2 132 199 / <alpha-value>)',
                    700: 'rgb(3 105 161 / <alpha-value>)',
                },
            },

            borderRadius: {
                control: 'var(--radius-control)',
                card: 'var(--radius-card)',
            },

            boxShadow: {
                card: 'var(--shadow-card)',
                overlay: 'var(--shadow-overlay)',
            },
        },
    },

    plugins: [forms],
};
