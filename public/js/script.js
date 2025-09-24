// Año dinámico
document.getElementById('year').textContent = new Date().getFullYear();

// Toggle menú mobile
const toggle = document.querySelector('.nav-toggle');
const menu = document.getElementById('nav-menu');
if (toggle && menu) {
    toggle.addEventListener('click', () => {
        const open = menu.classList.toggle('open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
}

// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
        if (e.isIntersecting) {
            e.target.classList.add('visible');
            observer.unobserve(e.target);
        }
    });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

/* ---------- Fondo animado (constelaciones) ---------- */
const canvas = document.getElementById('bg-canvas');
const ctx = canvas.getContext('2d', { alpha: true });

let W, H, dpr, points = [];
const color = 'rgba(249,159,19,';
const lineColor = 'rgba(207,211,214,)';

// control de motion
const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
function size() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    W = canvas.width = Math.floor(window.innerWidth * dpr);
    H = canvas.height = Math.floor(window.innerHeight * dpr);
    canvas.style.width = window.innerWidth + 'px';
    canvas.style.height = window.innerHeight + 'px';
}
size();
window.addEventListener('resize', size, { passive: true });

function initPoints() {
    points = [];
    const count = Math.floor(Math.min(80, Math.max(38, window.innerWidth / 28)));
    for (let i = 0; i < count; i++) {
        points.push({
            x: Math.random() * W,
            y: Math.random() * H,
            vx: (Math.random() - .5) * .06 * dpr,
            vy: (Math.random() - .5) * .06 * dpr,
            r: (Math.random() * 1.4 + .6) * dpr
        });
    }
}
initPoints();
window.addEventListener('resize', initPoints, { passive: true });

let last = 0;
function tick(t) {
    if (prefersReduced) return; // respetar accesibilidad
    const dt = Math.min(32, t - last); last = t;

    ctx.clearRect(0, 0, W, H);

    // actualizar y dibujar puntos
    for (let p of points) {
        p.x += p.vx * dt;
        p.y += p.vy * dt;

        // rebote suave
        if (p.x < 0 || p.x > W) p.vx *= -1;
        if (p.y < 0 || p.y > H) p.vy *= -1;

        ctx.beginPath();
        ctx.fillStyle = color + '0.55)';
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
    }

    // conectar puntos cercanos
    for (let i = 0; i < points.length; i++) {
        for (let j = i + 1; j < points.length; j++) {
            const a = points[i], b = points[j];
            const dx = a.x - b.x, dy = a.y - b.y;
            const dist2 = dx * dx + dy * dy;
            const max = (110 * dpr);
            if (dist2 < max * max) {
                const alpha = 0.1 * (1 - Math.sqrt(dist2) / max);
                ctx.strokeStyle = lineColor + alpha + ')';
                ctx.lineWidth = 0.8 * dpr * alpha;
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.stroke();
            }
        }
    }

    requestAnimationFrame(tick);
}
requestAnimationFrame(tick);

// Validación simple del formulario
const form = document.querySelector('.contact-form');
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const fields = [
            { id: 'nombre', valid: v => v.trim().length >= 3, msg: 'Ingresá tu nombre (mín. 3 caracteres).' },
            { id: 'email', valid: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), msg: 'Ingresá un email válido.' },
            { id: 'servicio', valid: v => v.trim() !== '', msg: 'Seleccioná un servicio.' },
        ];

        let ok = true;
        fields.forEach(f => {
            const el = document.getElementById(f.id);
            const err = el.parentElement.querySelector('.error');
            if (!f.valid(el.value)) {
                ok = false;
                err.textContent = f.msg;
                el.setAttribute('aria-invalid', 'true');
                el.focus({ preventScroll: true });
            } else {
                err.textContent = '';
                el.removeAttribute('aria-invalid');
            }
        });

        if (!ok) return;

        // TODO: integra tu endpoint real:
        // await fetch('/api/contacto', { method:'POST', body:new FormData(form) });
        form.reset();
        alert('¡Gracias! Te contactaremos en breve para coordinar la demo.');
    });
}
