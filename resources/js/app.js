import './bootstrap';
import './analytics';

/*
|--------------------------------------------------------------------------
| Gestão de Avarias - Core UI & Auth Integration
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {
    // 1. Verificar segurança e controlo de acessos antes de inicializar a UI
    if (!checkAuthRequirement()) return;

    // 2. Inicializar componentes visuais do ecossistema
    initTheme();
    initSidebar();
    initDropdowns();
    initTooltips();
    initAnimations();
});

/* --------------------------------------------------------------------------
   SEGURANÇA E AUTENTICAÇÃO (INTEGRAÇÃO SPA)
--------------------------------------------------------------------------- */

function checkAuthRequirement() {
    const token = localStorage.getItem('api_token') || getCookie('api_token');

    // Se a view Blade marcar que exige autenticação e não houver token, redireciona imediatamente
    if (window.requireAuthOnLoad && !token) {
        window.location.href = '/ui/login';
        return false;
    }
    return true;
}

// Helper global para injetar o cabeçalho de autorização nos fetchs das tuas views
window.authHeader = function () {
    const token = localStorage.getItem('api_token') || getCookie('api_token');
    return {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
};

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

/* --------------------------------------------------------------------------
   THEME (SINCRO CORRETA COM O LOCALSTORAGE)
--------------------------------------------------------------------------- */

function initTheme() {
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark') {
        html.classList.add('dark');
    } else if (savedTheme === 'light') {
        html.classList.remove('dark');
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        html.classList.add('dark');
    }

    document.querySelectorAll('[data-theme-toggle]').forEach(button => {
        button.addEventListener('click', () => window.toggleTheme());
    });
}

window.toggleTheme = function () {
    const html = document.documentElement;
    html.classList.toggle('dark');
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
};

/* --------------------------------------------------------------------------
   SIDEBAR
--------------------------------------------------------------------------- */

function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    document.querySelectorAll('[data-sidebar-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            sidebar?.classList.remove('-translate-x-full');
            overlay?.classList.remove('hidden');
        });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach(btn => {
        btn.addEventListener('click', closeSidebar);
    });

    overlay?.addEventListener('click', closeSidebar);

    function closeSidebar() {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    }
}

/* --------------------------------------------------------------------------
   DROPDOWNS (CORREÇÃO DE CONFLITOS DE MULTIPLOS MENUS)
--------------------------------------------------------------------------- */

function initDropdowns() {
    document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
        const button = dropdown.querySelector('[data-dropdown-button]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!button || !menu) return;

        button.addEventListener('click', (e) => {
            e.stopPropagation();

            // Fechar todos os outros dropdowns ativos para evitar sobreposição
            document.querySelectorAll('[data-dropdown-menu]').forEach(otherMenu => {
                if (otherMenu !== menu) otherMenu.classList.add('hidden');
            });

            menu.classList.toggle('hidden');
        });
    });

    // Clicar fora fecha qualquer dropdown aberto
    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown-menu]').forEach(menu => {
            menu.classList.add('hidden');
        });
    });
}

/* --------------------------------------------------------------------------
   TOOLTIPS
--------------------------------------------------------------------------- */

function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.title = element.dataset.tooltip;
    });
}

/* --------------------------------------------------------------------------
   ANIMAÇÕES DE ENTRADA (INTERSECTION OBSERVER)
--------------------------------------------------------------------------- */

function initAnimations() {
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.remove('opacity-0', 'translate-y-3');
                entry.target.classList.add('opacity-100', 'translate-y-0');
                observer.unobserve(entry.target); // Deixa de observar após animar uma vez
            }
        });
    }, {
        threshold: 0.05
    });

    document.querySelectorAll('[data-animate]').forEach(element => {
        element.classList.add(
            'opacity-0',
            'translate-y-3',
            'transition-all',
            'duration-500',
            'ease-out'
        );
        observer.observe(element);
    });
}

/* --------------------------------------------------------------------------
   NOTIFICAÇÕES FLUTUANTES (TOAST SYSTEM COM TAILWIND CLASSHES)
--------------------------------------------------------------------------- */

window.showToast = function (message, type = 'success') {
    const toast = document.createElement('div');

    // Configuração base de classes Tailwind e transições de opacidade/transformação
    toast.className = 'fixed top-6 right-6 z-50 rounded-xl px-4 py-3 shadow-2xl text-white font-medium text-sm ' +
                      'transform translate-y-2 opacity-0 transition-all duration-300 ease-out flex items-center gap-2';

    // Atribuição de classes de cor com base no tipo sem injetar CSS inline rígido
    switch (type) {
        case 'error':
            toast.classList.add('bg-red-500', 'dark:bg-red-600');
            break;
        case 'warning':
            toast.classList.add('bg-amber-500', 'dark:bg-amber-600');
            break;
        default:
            toast.classList.add('bg-emerald-500', 'dark:bg-emerald-600');
    }

    toast.textContent = message;
    document.body.appendChild(toast);

    // Despoleta a animação de entrada no frame seguinte
    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-2');
        toast.classList.add('opacity-100', 'translate-y-0');
    });

    // Remove o elemento do DOM de forma limpa após o fade-out
    setTimeout(() => {
        toast.classList.remove('opacity-100', 'translate-y-0');
        toast.classList.add('opacity-0', 'translate-y-[-8px]');

        setTimeout(() => toast.remove(), 300);
    }, 3500);
};
