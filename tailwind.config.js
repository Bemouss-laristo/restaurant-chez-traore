import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Accent « Chez Traoré » : ambre cuit, celui du logo.
                // 600 et au-delà passent le contraste de lisibilité (≥ 4,5)
                // pour du texte normal sur blanc et sur le fond crème.
                brand: {
                    50: '#fff8ed',
                    100: '#ffeed3',
                    200: '#fed9a6',
                    300: '#fdbc6d',
                    400: '#fb9534',
                    500: '#f5760e',
                    600: '#b95208',
                    700: '#97410c',
                    800: '#7a3510',
                    900: '#642d11',
                    950: '#3a1606',
                },
                // Brun profond de la barre latérale.
                cocoa: {
                    50: '#faf6f2',
                    100: '#f2e9e1',
                    200: '#e3d2c3',
                    300: '#cdb39c',
                    400: '#b08c6e',
                    500: '#946d51',
                    600: '#7a5641',
                    700: '#5e4133',
                    800: '#43302a',
                    900: '#2e211c',
                    950: '#1c1411',
                },
                cream: '#fcf8f3',
            },
            boxShadow: {
                soft: '0 1px 2px rgba(46, 33, 28, 0.04), 0 4px 16px -4px rgba(46, 33, 28, 0.08)',
                lift: '0 2px 4px rgba(46, 33, 28, 0.06), 0 12px 28px -8px rgba(46, 33, 28, 0.16)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'slide-down': {
                    '0%': { opacity: '0', transform: 'translateY(-8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                pop: {
                    '0%': { transform: 'scale(0.6)', opacity: '0' },
                    '70%': { transform: 'scale(1.12)', opacity: '1' },
                    '100%': { transform: 'scale(1)' },
                },
                'soft-pulse': {
                    '0%, 100%': { boxShadow: '0 0 0 0 rgba(245, 118, 14, 0.45)' },
                    '50%': { boxShadow: '0 0 0 6px rgba(245, 118, 14, 0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.45s cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 0.3s ease-out both',
                'slide-down': 'slide-down 0.35s cubic-bezier(0.22, 1, 0.36, 1) both',
                pop: 'pop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both',
                'soft-pulse': 'soft-pulse 2s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
