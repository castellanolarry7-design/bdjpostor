/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    './index.html',
    './src/**/*.{vue,js,ts}',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50:  '#eef2ff',
          100: '#e0e7ff',
          200: '#c7d2fe',
          300: '#a5b4fc',
          400: '#818cf8',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
          800: '#3730a3',
          900: '#312e81',
          950: '#1e1b4b',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        '2xl': '1rem',
        '3xl': '1.25rem',
      },
      boxShadow: {
        'soft':    '0 2px 8px -1px rgba(15,23,42,0.08), 0 1px 3px -1px rgba(15,23,42,0.05)',
        'medium':  '0 8px 20px -4px rgba(15,23,42,0.10), 0 3px 8px -3px rgba(15,23,42,0.06)',
        'large':   '0 20px 40px -8px rgba(15,23,42,0.14), 0 8px 16px -6px rgba(15,23,42,0.08)',
        'primary': '0 4px 14px rgba(99,102,241,0.40)',
        'danger':  '0 4px 14px rgba(244,63,94,0.40)',
      },
      animation: {
        'fade-up':   'fadeUp 0.3s ease both',
        'fade-in':   'fadeIn 0.2s ease both',
        'scale-in':  'scaleIn 0.2s ease both',
      },
      keyframes: {
        fadeUp:  { from: { opacity: '0', transform: 'translateY(10px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
        fadeIn:  { from: { opacity: '0' }, to: { opacity: '1' } },
        scaleIn: { from: { opacity: '0', transform: 'scale(0.96)' }, to: { opacity: '1', transform: 'scale(1)' } },
      },
    },
  },
  plugins: [],
}
