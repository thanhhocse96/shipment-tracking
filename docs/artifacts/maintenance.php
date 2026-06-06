<?php
if ( ! headers_sent() ) {
	header( 'HTTP/1.1 503 Service Unavailable' );
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'Retry-After: 3600' );
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Site under maintenance - Minh Hai Fishery</title>
<style>
html,
body {
  min-height: 100%;
  margin: 0;
  background: #001a33;
}

body.page-id-33 .site-footer,
body.page-id-33 .entry-title {
  display: none;
}

body.page-id-33.separate-containers .inside-article,
body.page-id-33 .entry-content {
  margin: 0;
  padding: 0;
}

body.page-id-33 .site-main {
  margin: 0;
}

.mhf-maintenance,
.mhf-maintenance * {
  box-sizing: border-box;
}

.mhf-maintenance {
  --mhf-blue-950: #001a33;
  --mhf-blue-900: #00284a;
  --mhf-blue-700: #0077b6;
  --mhf-cyan-500: #00b4d8;
  --mhf-cyan-300: #a8e6f5;
  --mhf-white: #ffffff;

  position: relative;
  width: 100vw;
  /*
   * Standalone maintenance.php mode:
   * WordPress includes this file directly during updates, outside the theme/page shell.
   * Do not subtract GeneratePress header, footer, or admin-bar heights here; use the full viewport.
   */
  min-height: 100svh;
  margin-left: 0;
  margin-right: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: var(--mhf-blue-950);
  color: var(--mhf-white);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

body.admin-bar .mhf-maintenance {
  /*
   * Defensive only. In real update-maintenance mode the admin bar is not rendered.
   * Keep full height so this standalone file cannot inherit the page-artifact offsets.
   */
  min-height: 100svh;
}

.mhf-maintenance__bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  z-index: 0;
  animation: mhfMaintenanceSlowZoom 30s ease-in-out infinite alternate;
}

.mhf-maintenance__overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  background: linear-gradient(180deg, rgba(0, 30, 60, 0.86) 0%, rgba(0, 25, 50, 0.72) 42%, rgba(0, 30, 60, 0.82) 100%);
}

.mhf-maintenance__overlay::after {
  content: "";
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at center, transparent 30%, rgba(0, 15, 35, 0.4) 100%);
}

.mhf-maintenance__fish-tank {
  position: absolute;
  inset: 0;
  z-index: 2;
  pointer-events: none;
  overflow: hidden;
}

.mhf-maintenance__fish {
  position: absolute;
  font-size: clamp(20px, 2.2vw, 28px);
  opacity: 0.72;
  filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.3)) blur(0.3px);
  will-change: transform;
}

.mhf-maintenance__fish:nth-child(1) {
  top: 18%;
  left: -10%;
  animation: mhfMaintenanceSwim1 38s linear infinite;
}

.mhf-maintenance__fish:nth-child(2) {
  top: 68%;
  left: -10%;
  font-size: 22px;
  opacity: 0.6;
  animation: mhfMaintenanceSwim2 45s linear infinite;
  animation-delay: 4s;
}

.mhf-maintenance__fish:nth-child(3) {
  top: 38%;
  left: -10%;
  font-size: 24px;
  animation: mhfMaintenanceSwim3 52s linear infinite;
  animation-delay: 12s;
}

.mhf-maintenance__fish:nth-child(4) {
  top: 82%;
  left: -10%;
  font-size: 18px;
  opacity: 0.55;
  animation: mhfMaintenanceSwim1 41s linear infinite;
  animation-delay: 20s;
}

.mhf-maintenance__fish:nth-child(5) {
  top: 12%;
  left: -10%;
  font-size: 20px;
  opacity: 0.5;
  animation: mhfMaintenanceSwim2 48s linear infinite;
  animation-delay: 28s;
}

.mhf-maintenance__content {
  position: relative;
  z-index: 5;
  width: min(100%, 720px);
  padding: 24px;
  margin: 0 auto;
}

.mhf-maintenance__card {
  position: relative;
  overflow: hidden;
  padding: clamp(36px, 6vw, 64px) clamp(28px, 5vw, 56px);
  border: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 28px;
  background: rgba(255, 255, 255, 0.08);
  box-shadow:
    0 25px 70px rgba(0, 20, 50, 0.5),
    0 5px 20px rgba(0, 30, 70, 0.3),
    inset 0 1px 0 rgba(255, 255, 255, 0.12);
  text-align: center;
  backdrop-filter: blur(24px) saturate(170%);
  -webkit-backdrop-filter: blur(24px) saturate(170%);
  animation: mhfMaintenanceFadeInUp 900ms cubic-bezier(0.2, 0.8, 0.2, 1) both;
}

.mhf-maintenance__card::before {
  content: "";
  position: absolute;
  inset: -1px;
  border-radius: 28px;
  padding: 1px;
  background: linear-gradient(135deg, rgba(0, 180, 216, 0.4), rgba(255, 255, 255, 0.1), rgba(0, 119, 182, 0.3));
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events: none;
}

.mhf-maintenance__badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 28px;
  padding: 8px 16px;
  border: 1px solid rgba(0, 180, 216, 0.3);
  border-radius: 999px;
  background: rgba(0, 180, 216, 0.15);
  color: var(--mhf-cyan-300);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0;
  backdrop-filter: blur(10px);
  animation: mhfMaintenanceFloat 3s ease-in-out infinite;
}

.mhf-maintenance__badge-dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
  background: #00d4ff;
  box-shadow: 0 0 10px #00d4ff;
  animation: mhfMaintenancePulse 2s ease-in-out infinite;
}

.mhf-maintenance__title {
  max-width: 12em;
  margin: 0 auto 18px;
  color: var(--mhf-white);
  font-size: clamp(34px, 5.5vw, 56px);
  font-weight: 800;
  letter-spacing: 0;
  line-height: 1.05;
}

.mhf-maintenance__subtitle {
  max-width: 520px;
  margin: 0 auto 36px;
  color: rgba(220, 240, 255, 0.86);
  font-size: clamp(15px, 1.9vw, 18px);
  font-weight: 400;
  line-height: 1.7;
}

.mhf-maintenance__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  justify-content: center;
  margin-bottom: 12px;
}

.mhf-maintenance__button {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  padding: 13px 26px;
  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.94);
  font-size: 15px;
  font-weight: 700;
  line-height: 1.2;
  text-decoration: none;
  white-space: nowrap;
  backdrop-filter: blur(10px);
  transition: transform 180ms ease, background 180ms ease, border-color 180ms ease;
}

.mhf-maintenance__button:hover,
.mhf-maintenance__button:focus-visible {
  border-color: rgba(255, 255, 255, 0.28);
  background: rgba(255, 255, 255, 0.14);
  color: #ffffff;
  transform: translateY(-1px);
}

.mhf-maintenance__waves-container {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 140px;
  z-index: 3;
  pointer-events: none;
  overflow: hidden;
}

.mhf-maintenance__waves {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 200%;
  height: 100%;
}

.mhf-maintenance__wave {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-repeat: repeat-x;
}

.mhf-maintenance__wave--1 {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 1200 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,60 C200,100 400,20 600,60 C800,100 1000,20 1200,60 L1200,120 L0,120 Z' fill='rgba(255,255,255,0.08)'/%3E%3C/svg%3E");
  background-size: 1200px 120px;
  opacity: 0.8;
  animation: mhfMaintenanceWaveMove 20s linear infinite;
}

.mhf-maintenance__wave--2 {
  bottom: -5px;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 1200 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,70 C250,30 500,90 750,70 C1000,50 1100,90 1200,70 L1200,120 L0,120 Z' fill='rgba(0,180,216,0.12)'/%3E%3C/svg%3E");
  background-size: 1200px 120px;
  opacity: 0.6;
  animation: mhfMaintenanceWaveMove 15s linear infinite reverse;
}

.mhf-maintenance__wave--3 {
  bottom: -10px;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 1200 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0,80 C300,50 600,100 900,75 C1100,60 1150,85 1200,80 L1200,120 L0,120 Z' fill='rgba(255,255,255,0.05)'/%3E%3C/svg%3E");
  background-size: 1200px 120px;
  opacity: 0.4;
  animation: mhfMaintenanceWaveMove 25s linear infinite;
}

.mhf-maintenance__footnote {
  position: absolute;
  bottom: 24px;
  left: 50%;
  z-index: 6;
  transform: translateX(-50%);
  padding: 8px 20px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.22);
  color: rgba(255, 255, 255, 0.62);
  font-size: 13px;
  font-weight: 400;
  letter-spacing: 0;
  text-align: center;
  backdrop-filter: blur(10px);
  animation: mhfMaintenanceFadeIn 1.2s ease-out 0.45s both;
}

@keyframes mhfMaintenanceSlowZoom {
  0% {
    transform: scale(1);
  }
  100% {
    transform: scale(1.08);
  }
}

@keyframes mhfMaintenanceSwim1 {
  0% {
    transform: translateX(0) translateY(0) rotate(0deg) scaleX(-1);
  }
  25% {
    transform: translateX(25vw) translateY(-15px) rotate(3deg) scaleX(-1);
  }
  50% {
    transform: translateX(55vw) translateY(10px) rotate(-2deg) scaleX(-1);
  }
  75% {
    transform: translateX(80vw) translateY(-8px) rotate(2deg) scaleX(-1);
  }
  100% {
    transform: translateX(115vw) translateY(5px) rotate(0deg) scaleX(-1);
  }
}

@keyframes mhfMaintenanceSwim2 {
  0% {
    transform: translateX(0) translateY(0) scaleX(-1);
  }
  50% {
    transform: translateX(60vw) translateY(-20px) scaleX(-1);
  }
  100% {
    transform: translateX(115vw) translateY(15px) scaleX(-1);
  }
}

@keyframes mhfMaintenanceSwim3 {
  0% {
    transform: translateX(0) translateY(0) rotate(-2deg) scaleX(-1);
  }
  33% {
    transform: translateX(35vw) translateY(12px) rotate(4deg) scaleX(-1);
  }
  66% {
    transform: translateX(70vw) translateY(-10px) rotate(-3deg) scaleX(-1);
  }
  100% {
    transform: translateX(115vw) translateY(8px) rotate(1deg) scaleX(-1);
  }
}

@keyframes mhfMaintenanceWaveMove {
  0% {
    transform: translateX(0);
  }
  100% {
    transform: translateX(-50%);
  }
}

@keyframes mhfMaintenanceFadeInUp {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes mhfMaintenanceFloat {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}

@keyframes mhfMaintenancePulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.2);
  }
}

@keyframes mhfMaintenanceFadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@media (max-width: 640px) {
  .mhf-maintenance {
    min-height: 100svh;
  }

  body.admin-bar .mhf-maintenance {
    min-height: 100svh;
  }

  .mhf-maintenance__content {
    padding: 20px;
  }

  .mhf-maintenance__card {
    padding: 40px 24px;
    border-radius: 24px;
  }

  .mhf-maintenance__actions {
    flex-direction: column;
    width: 100%;
  }

  .mhf-maintenance__button {
    width: 100%;
  }

  .mhf-maintenance__fish {
    font-size: 18px;
  }

  .mhf-maintenance__waves-container {
    height: 100px;
  }

  .mhf-maintenance__footnote {
    bottom: 16px;
    padding: 6px 16px;
    font-size: 12px;
  }
}

@media (max-width: 375px) {
  .mhf-maintenance__title {
    font-size: 30px;
  }

  .mhf-maintenance__subtitle {
    font-size: 14px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .mhf-maintenance *,
  .mhf-maintenance *::before,
  .mhf-maintenance *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }

  .mhf-maintenance__bg {
    animation: none;
  }
}
</style>
</head>
<body>

<section class="mhf-maintenance" aria-label="Site under maintenance">
  <img src="https://minhhaifishery.com/wp-content/uploads/2026/05/Minh-Hai-Fishery-Fish-background.webp" alt="Ocean background with fish and soft waves" class="mhf-maintenance__bg" loading="eager" />
  <div class="mhf-maintenance__overlay" aria-hidden="true"></div>

  <div class="mhf-maintenance__fish-tank" aria-hidden="true">
    <span class="mhf-maintenance__fish">🐠</span>
    <span class="mhf-maintenance__fish">🐟</span>
    <span class="mhf-maintenance__fish">🐡</span>
    <span class="mhf-maintenance__fish">🐠</span>
    <span class="mhf-maintenance__fish">🐟</span>
  </div>

  <div class="mhf-maintenance__waves-container" aria-hidden="true">
    <div class="mhf-maintenance__waves">
      <div class="mhf-maintenance__wave mhf-maintenance__wave--1"></div>
      <div class="mhf-maintenance__wave mhf-maintenance__wave--2"></div>
      <div class="mhf-maintenance__wave mhf-maintenance__wave--3"></div>
    </div>
  </div>

  <div class="mhf-maintenance__content">
    <div class="mhf-maintenance__card">
      <div class="mhf-maintenance__badge">
        <span class="mhf-maintenance__badge-dot"></span>
        <span>Maintenance in progress</span>
      </div>

      <h1 class="mhf-maintenance__title">Site under maintenance</h1>

      <p class="mhf-maintenance__subtitle">
        We are updating the website to provide a better experience. Please check back soon.
      </p>

      <div class="mhf-maintenance__actions">
        <a href="mailto:mhf@minhhaifishery.com" class="mhf-maintenance__button">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
          </svg>
          <span>Contact us</span>
        </a>
      </div>
    </div>
  </div>

  <footer class="mhf-maintenance__footnote">© 2026 - Under maintenance</footer>
</section>
</body>
</html>
