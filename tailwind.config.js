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
            colors: {
                // Arena & Court Dark Palette (Indoor Stadium feel, deep navy/charcoal)
                arena: {
                    base: '#0A0F1D',
                    card: '#111A2E',
                    surface: '#18233C',
                    border: '#24324F',
                    light: '#F8FAFC',
                },
                // Primary Accent: Smash Volt (Shuttlecock neon lime / court line green)
                volt: {
                    DEFAULT: '#CCFF00',
                    hover: '#B5E600',
                    light: '#F4FFCC',
                    deep: '#15803D',
                    contrast: '#0A0F1D',
                },
                // Secondary Accent: Speed Orange (Shuttlecock feather / urgent state)
                courtOrange: {
                    DEFAULT: '#FF5500',
                    hover: '#E04A00',
                    light: '#FFF0EA',
                    contrast: '#FFFFFF',
                },
                // Court Slate (Neutral cool athletic slate)
                courtSlate: {
                    900: '#0F172A',
                    800: '#1E293B',
                    700: '#334155',
                    500: '#64748B',
                    400: '#94A3B8',
                    300: '#CBD5E1',
                    200: '#E2E8F0',
                    100: '#F1F5F9',
                    50: '#F8FAFC',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'Figtree', ...defaultTheme.fontFamily.sans],
                display: ['"Barlow Condensed"', 'sans-serif'],
            },
            boxShadow: {
                'volt-glow': '0 0 25px -5px rgba(204, 255, 0, 0.35)',
                'volt-glow-sm': '0 0 12px -2px rgba(204, 255, 0, 0.40)',
                'orange-glow': '0 0 20px -4px rgba(255, 85, 0, 0.35)',
                'card-elevated': '0 12px 30px -10px rgba(10, 15, 29, 0.12)',
                'card-active': '0 14px 34px -8px rgba(204, 255, 0, 0.25), 0 4px 12px -2px rgba(10, 15, 29, 0.08)',
            },
            letterSpacing: {
                athletic: '0.04em',
                condensed: '-0.02em',
            },
        },
    },

    plugins: [forms],
};
