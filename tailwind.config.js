/** @type {import('tailwindcss').Config} */
export default {
  corePlugins: { preflight: false }, // 👈 evita que Tailwind resetee estilos
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.php',
    './resources/**/*.js',
    './resources/**/*.ts',
    './resources/**/*.vue',
  ],
  theme: { extend: {} },
  plugins: [],
}
