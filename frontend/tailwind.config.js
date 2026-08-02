/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,jsx}',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#4B262F',
          dark: '#351B22',
          soft: '#E8CDD2',
        },
        accent: {
          DEFAULT: '#EED3BA',
          soft: '#F7E8D8',
        },
        surface: {
          canvas: '#F8F3EC',
          DEFAULT: '#F8F3EC',
          muted: '#EFE7DE',
          elevated: '#FFFDF9',
          strong: '#DCCDBC',
        },
        sidebar: {
          DEFAULT: '#151311',
          elevated: '#241F1B',
          active: '#332B25',
        },
        content: {
          DEFAULT: '#151311',
          strong: '#2A2420',
          secondary: '#6F5D55',
          muted: '#9B8578',
          inverse: '#F8F3EC',
        },
        border: {
          DEFAULT: '#DCCDBC',
          strong: '#C9B9A7',
          dark: '#332B25',
        },
        status: {
          success: '#56705F',
          warning: '#AD7039',
          danger: '#7A2E3C',
          info: '#586B7D',
        },
      },
      fontFamily: {
        heading: ['Inter', 'sans-serif'],
        body:    ['Inter', 'sans-serif'],
      },
      backdropBlur: {
        xs: '2px',
      },
    },
  },
  plugins: [],
}
