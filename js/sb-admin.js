


// Loads the correct sidebar on window load and resizes
function handleSidebarCollapse() {
    var width = window.innerWidth;
    console.log(width);
    var sidebar = document.querySelector('div.sidebar-collapse');
    if (!sidebar) return;
    if (width < 768) {
        sidebar.classList.add('collapse');
    } else {
        sidebar.classList.remove('collapse');
    }
}

window.addEventListener('load', handleSidebarCollapse);
window.addEventListener('resize', handleSidebarCollapse);
