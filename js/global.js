class SidebarManager {
    constructor(menuToggleSelector, sidebarSelector) {
        this.menuToggle = document.querySelector(menuToggleSelector);
        this.sidebar = document.querySelector(sidebarSelector);

        if (this.menuToggle && this.sidebar) {
            this.init();
        } else {
            console.error('Nie znaleziono elementów menuToggle lub sidebar.');
        }
    }

    init() {
        this.menuToggle.addEventListener("click", () => this.toggleSidebar());
    }

    toggleSidebar() {
        this.sidebar.classList.toggle("active");
    }
}

class ScaleDetector {
    constructor() {
        this.scales = {
            1: 'scale-1',
            1.25: 'scale-1-25',
            1.5: 'scale-1-5',
            2: 'scale-2',
        };
        this.init();
    }

    init() {
        this.detectScale();
        window.addEventListener('resize', () => this.detectScale());
    }

    detectScale() {
        const scale = window.devicePixelRatio;

        Object.values(this.scales).forEach(scaleClass => {
            document.documentElement.classList.remove(scaleClass);
        });

        const scaleClass = this.scales[scale] || null;
        if (scaleClass) {
            document.documentElement.classList.add(scaleClass);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new SidebarManager('.menu-toggle', '.sidebar');
    new ScaleDetector();
});
