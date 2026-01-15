
<!-- Core Scripts - Include with every page -->
<script src="js/bootstrap-5.3.3.bundle.min.js"></script>

<!-- Page-Level Plugin Scripts - Dashboard (Chart.js from CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- SB Admin Scripts - Include with every page -->
<script src="js/sb-admin.js"></script>
<!-- jqBootstrapValidation.js and smart-dropdown.js removed: use Bootstrap 5 validation and dropdowns instead -->

<!-- Page-Level Demo Scripts - Dashboard (Chart.js) -->
<script src="js/demo/dashboard-charts.js"></script>
<script>
function Conf(object) {
    if (confirm("Do u want to delete?") == true) {
        return true;
    }
    return false;
}
</script>