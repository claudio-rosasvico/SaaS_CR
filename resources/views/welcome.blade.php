<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Shift+IA — Cambia de nivel con IA</title>
    <meta name="description" content="Chatbots inteligentes y backoffice a medida. Shift+IA: Cambia de nivel con IA." />
    <meta name="theme-color" content="#18252d" />

    <!-- Open Graph -->
    <meta property="og:title" content="Shift+IA — Cambia de nivel con IA" />
    <meta property="og:description" content="Automatiza con chatbots y backoffice a medida." />
    <meta property="og:type" content="website" />
    <meta property="og:image" content="img/og-image.png" />
    <meta property="og:url" content="https://tusitio.com" />

    <!-- Fuentes -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Estilos -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
    <link rel="icon" href="img/favicon.png" type="image/png" />
</head>

<body>
    <!-- Lienzo de fondo (constelaciones) -->
    <canvas id="bg-canvas" aria-hidden="true"></canvas>
    <div class="bg-overlay" aria-hidden="true"></div>
    <div class="bg-noise" aria-hidden="true"></div>

    <!-- Header -->
    <header class="site-header" id="top">
        <div class="container header-inner">
            <a href="#top" class="brand" aria-label="Shift+IA inicio">
                {{-- <svg class="logo" viewBox="0 0 172 40" aria-hidden="true">
                    <rect x="0" y="2" width="42" height="36" rx="9" fill="#0f171d" stroke="#cfd3d6"
                        stroke-width="1.2" opacity=".85" />
                    <!-- flecha shift -->
                    <path d="M21 10 L32 23 H26 V29 H16 V23 H10 Z" fill="#f99f13" />
                    <text x="52" y="27" font-family="Montserrat" font-weight="800" font-size="20" fill="#ffffff"
                        letter-spacing=".2">Shift</text>
                    <text x="94" y="27" font-family="Montserrat" font-weight="800" font-size="20"
                        fill="#f99f13">+</text>
                    <text x="108" y="27" font-family="Montserrat" font-weight="800" font-size="20"
                        fill="#ffffff">IA</text>
                </svg> --}}
                <img src="{{ asset('images/ico_sin_claim.png') }}" alt="Logo de Shift+IA" srcset="" style="width: 20%">
            </a>

            <nav class="nav" aria-label="Principal">
{{--                 <button class="nav-toggle" aria-expanded="false" aria-controls="nav-menu" aria-label="Abrir menú">
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                    <span class="nav-toggle-bar"></span>
                </button> --}}
                <ul id="nav-menu" class="nav-menu">
                    <li><a href="#servicios">Servicios</a></li>
                    <li><a href="#como-funciona">Cómo funciona</a></li>
                    <li><a href="#beneficios">Beneficios</a></li>
                    <li><a class="btn btn-primary" href="#contacto">Solicitar demo</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero reveal" aria-labelledby="hero-title">
        <div class="container hero-inner">
            <div class="hero-copy">
                <h1 id="hero-title" class="display">
                    <span class="badge">Nuevo</span> Shift+IA
                </h1>
                <p class="claim">Cambia de nivel con IA</p>
                <p class="subtitle">
                    Implementamos <strong>chatbots inteligentes</strong> y <strong>backoffice a medida</strong> con una
                    capa UI/UX pulida, para que la automatización se sienta natural.
                </p>
                <div class="hero-cta">
                    <a class="btn btn-primary" href="#contacto">Quiero mi demo</a>
                    <a class="btn btn-ghost" href="#servicios">Ver servicios</a>
                </div>

                <ul class="hero-bullets" role="list">
                    <li>Respuestas 24/7 con identidad de marca</li>
                    <li>Procesos fluidos y medibles</li>
                    <li>Integración con webs, CRM y sistemas</li>
                </ul>
            </div>

            <!-- Arte hero: malla sutil animada -->
            <div class="hero-art" aria-hidden="true">
                <div class="orb orb-a"></div>
                <div class="orb orb-b"></div>
                <svg class="mesh" viewBox="0 0 420 340">
                    <defs>
                        <linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#f99f13" />
                            <stop offset="100%" stop-color="#cfd3d6" />
                        </linearGradient>
                    </defs>
                    <path class="dash" d="M10,290 C120,260 180,310 240,250 C300,190 330,220 410,170" fill="none"
                        stroke="url(#g1)" stroke-width="1.6" opacity=".55" />
                    <path class="dash delay" d="M10,210 C110,220 170,150 240,190 C310,230 350,180 410,200"
                        fill="none" stroke="#f99f13" stroke-width="1.2" opacity=".6" />
                    <path class="dash slow" d="M10,140 C100,160 200,110 260,140 C320,170 360,120 410,140"
                        fill="none" stroke="#cfd3d6" stroke-width="1" opacity=".5" />
                </svg>
            </div>
        </div>
    </section>

    <!-- Servicios -->
    <section id="servicios" class="section services reveal" aria-labelledby="servicios-title">
        <div class="container">
            <header class="section-header">
                <h2 id="servicios-title">Servicios</h2>
                <p>Soluciones de alto impacto, diseño con intención.</p>
            </header>

            <div class="cards">
                <!-- Chatbots -->
                <article class="card">
                    <div class="card-icon">
                        <span class="icon-bubble">+</span>
                    </div>
                    <h3>Chatbots Inteligentes</h3>
                    <p>Atención 24/7, lead gen y soporte interno. Entrenados con tus documentos/URLs/BD, tono de marca y
                        flujos a medida.</p>
                    <ul class="features" role="list">
                        <li>Omnicanal: web, WhatsApp, redes</li>
                        <li>Entrenamiento con PDFs/URLs/BD</li>
                        <li>Analíticas y mejora continua</li>
                    </ul>
                    <a class="link" href="#contacto">Quiero un chatbot →</a>
                </article>

                <!-- Backoffice -->
                <article class="card">
                    <div class="card-icon">
                        <span class="icon-dash">↑</span>
                    </div>
                    <h3>Backoffice a Medida</h3>
                    <p>Tu “cerebro digital”: inventario, ventas, clientes y reportes en una interfaz ágil, escalable y
                        hermosa.</p>
                    <ul class="features" role="list">
                        <li>UI/UX moderna y accesible</li>
                        <li>Roles, permisos y auditoría</li>
                        <li>Integración con ERP/CRM</li>
                    </ul>
                    <a class="link" href="#contacto">Impulsar mi backoffice →</a>
                </article>
            </div>
        </div>
    </section>

    <!-- Cómo Funciona -->
    <section id="como-funciona" class="section how reveal" aria-labelledby="como-title">
        <div class="container">
            <header class="section-header">
                <h2 id="como-title">Cómo funciona</h2>
                <p>Proceso minimalista, resultados medibles.</p>
            </header>

            <ol class="steps">
                <li>
                    <span class="step-dot">1</span>
                    <h3>Descubrimiento</h3>
                    <p>Mapeamos tareas, fricciones y KPIs. Priorizamos quick wins.</p>
                </li>
                <li>
                    <span class="step-dot">2</span>
                    <h3>Diseño & PoC</h3>
                    <p>Prototipo funcional del chatbot o módulo del backoffice. Iteramos rápido.</p>
                </li>
                <li>
                    <span class="step-dot">3</span>
                    <h3>Integración & Escala</h3>
                    <p>Conectamos con tus sistemas, automatizamos y medimos impacto.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Beneficios -->
    <section id="beneficios" class="section benefits reveal" aria-labelledby="beneficios-title">
        <div class="container">
            <header class="section-header">
                <h2 id="beneficios-title">Beneficios</h2>
                <p>Lo que ganás al subir de nivel.</p>
            </header>

            <div class="benefit-grid">
                <article class="benefit">
                    <h3>+ Eficiencia</h3>
                    <p>Menos repetición, más foco estratégico.</p>
                </article>
                <article class="benefit">
                    <h3>+ Conversión</h3>
                    <p>Respuestas inmediatas y personalizadas.</p>
                </article>
                <article class="benefit">
                    <h3>+ Control</h3>
                    <p>Dashboards y reportes accionables.</p>
                </article>
                <article class="benefit">
                    <h3>+ Escala</h3>
                    <p>Arquitectura preparada para crecer.</p>
                </article>
            </div>

            <div class="cta-band">
                <p>¿Listo para <strong>cambiar de nivel con IA</strong>?</p>
                <a class="btn btn-primary btn-lg" href="#contacto">Agendar demo</a>
            </div>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="section contact reveal" aria-labelledby="contacto-title">
        <div class="container">
            <header class="section-header">
                <h2 id="contacto-title">Contacto</h2>
                <p>Contanos tu caso y te proponemos una demo.</p>
            </header>

            <form class="contact-form" action="{{ route('contact.store') }}" method="POST" novalidate>
                @csrf
                <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">
                <div class="form-row">
                    <label for="nombre">Nombre y apellido</label>
                    <input id="nombre" name="nombre" type="text" autocomplete="name" required
                        placeholder="Ej: Ana Gómez" />
                    <span class="error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        placeholder="Ej: ana@empresa.com" />
                    <span class="error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="empresa">Empresa</label>
                    <input id="empresa" name="empresa" type="text" placeholder="Opcional" />
                </div>

                <div class="form-row">
                    <label for="servicio">Servicio de interés</label>
                    <select id="servicio" name="servicio" required>
                        <option value="">Elegí una opción</option>
                        <option value="chatbots">Chatbots Inteligentes</option>
                        <option value="backoffice">Backoffice a Medida</option>
                    </select>
                    <span class="error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="mensaje">Mensaje</label>
                    <textarea id="mensaje" name="mensaje" rows="4" placeholder="Contanos qué querés automatizar"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enviar consulta</button>
                    <p class="form-note">Respondemos en menos de 24 h hábiles.</p>
                </div>
            </form>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-brand">
                <span class="logo-mini" aria-hidden="true">+</span>
                <strong>Shift+IA</strong>
            </div>
            <nav aria-label="Legal">
                <a href="#" rel="nofollow">Privacidad</a>
                <a href="#" rel="nofollow">Términos</a>
                <a href="#top">Volver arriba ↑</a>
            </nav>
            <p class="copy">© <span id="year"></span> Shift+IA. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="{{ asset('js/script.js') }}"></script>
</body>

</html>
