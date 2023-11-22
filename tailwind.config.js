/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./app/views/**/*.{php,html,js}"],
  theme: {
    container: {
      center: true,
      padding: "16px",
    },
    extend: {
      screens: {
        "2xl": "1320px",
      },
      colors: {
        green: "#006401",
      },
    },
  },
  plugins: [],
};
