    <!-- Core Scripts - Include with every page -->
    <?php 
    $useBootstrap5 = isset($USE_BOOTSTRAP_5) && $USE_BOOTSTRAP_5;
    if ($useBootstrap5): ?>
    <script src="js/bootstrap-5.3.3.bundle.min.js"></script>
    <?php else: ?>
    <script src="js/bootstrap.min.js"></script>
    <?php endif; ?>
    


    <!-- Page-Level Plugin Scripts - Dashboard (Chart.js from CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>
    <!-- jqBootstrapValidation.js and smart-dropdown.js removed: use Bootstrap 5 validation and dropdowns instead -->

    <!-- Page-Level Demo Scripts - Dashboard (Chart.js) -->
    <script src="js/demo/dashboard-charts.js"></script>
    <script language="javascript">
function Conf(object) {
if (confirm("Do u want to delete?") == true) {
return true;
}
return false;
}


</script>