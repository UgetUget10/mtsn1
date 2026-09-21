// PM2 process manager — menjalankan website MTsN 1 tanpa terminal `npm run dev`.
//
//   cd C:\laragon\www\mtsn1\frontend && npm run build   (sekali, tiap ubah kode FE)
//   pm2 start ecosystem.config.js
//   pm2 save
//
// Apache (mtsn1.test) tetap mem-proxy /  -> 127.0.0.1:3000  dan  /api,/admin -> :8000
//
// Perintah harian:
//   pm2 status | pm2 logs | pm2 restart mtsn1-web | pm2 stop all

module.exports = {
  apps: [
    {
      name: "mtsn1-web",
      cwd: "C:/laragon/www/mtsn1/frontend",
      // jalankan binary Next langsung (PM2 Windows tidak bisa "npm run start")
      script: "node_modules/next/dist/bin/next",
      args: "start -p 3000",
      interpreter: "node",
      env: { NODE_ENV: "production" },
      autorestart: true,
      max_restarts: 10,
      windowsHide: true,
    },
    {
      name: "mtsn1-api",
      cwd: "C:/laragon/www/mtsn1/backend",
      script: "artisan",
      args: "serve --host=127.0.0.1 --port=8000",
      interpreter: "C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe", // pakai path lengkap php.exe bila "php" tak ada di PATH
      autorestart: true,
      max_restarts: 10,
      windowsHide: true,
    },
  ],
};
