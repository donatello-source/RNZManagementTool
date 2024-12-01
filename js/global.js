const menuToggle = document.querySelector(".menu-toggle");
const sidebar = document.querySelector(".sidebar");

menuToggle.addEventListener("click", () => {
    sidebar.classList.toggle("active");
});


function detectScale() {
    const scale = window.devicePixelRatio;

    document.documentElement.classList.remove('scale-1', 'scale-1-25', 'scale-1-5', 'scale-2');

    if (scale === 1) {
        document.documentElement.classList.add('scale-1');
    } else if (scale === 1.25) {
        document.documentElement.classList.add('scale-1-25');
    } else if (scale === 1.5) {
        document.documentElement.classList.add('scale-1-5');
    } else if (scale >= 2) {
        document.documentElement.classList.add('scale-2');
    }
}

detectScale();
window.addEventListener('resize', detectScale);
