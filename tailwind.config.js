import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Nunito Sans"', ...defaultTheme.fontFamily.sans],
                heading: ['"Varela Round"', '"Nunito Sans"', 'sans-serif'],
            },
            colors: {
                cream: '#F6EED9',
                brand: {
                    50: '#FFF9F2',
                    100: '#FFF3E6',
                    200: '#FEE1C0',
                    300: '#FEC98D',
                    400: '#FEAC4D',
                    500: '#FD8801',
                    600: '#CF7001',
                    700: '#9D5401',
                    800: '#723D00',
                    900: '#472600',
                    950: '#261400',
                },
            },
        },
    },

    plugins: [forms, typography],
};