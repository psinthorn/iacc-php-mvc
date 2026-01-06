    <!-- Core Scripts - Include with every page -->
    <?php 
    $useBootstrap5 = isset($USE_BOOTSTRAP_5) && $USE_BOOTSTRAP_5;
    if ($useBootstrap5): ?>
    <script src="js/bootstrap-5.3.3.bundle.min.js"></script>
    <?php else: ?>
    <script src="js/bootstrap.min.js"></script>
    <?php endif; ?>
    
    <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>

    <!-- Page-Level Plugin Scripts - Dashboard -->
    <script src="js/plugins/morris/raphael-2.1.0.min.js"></script>
    <script src="js/plugins/morris/morris.js"></script>

    <!-- SB Admin Scripts - Include with every page -->
    <script src="js/sb-admin.js"></script>
    <script src="js/jqBootstrapValidation.js"></script>
    
    <!-- Smart Dropdown Component - Searchable & Sortable dropdowns -->
    <script src="js/smart-dropdown.js"></script>
    <script>
        // Auto-initialize all smart dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof SmartDropdown !== 'undefined') {
                SmartDropdown.initAll('.smart-dropdown');
            }
        });
    </script>

    <!-- Page-Level Demo Scripts - Dashboard - Use for reference -->
    <script src="js/demo/dashboard-demo.js">

    </script><script language="javascript">
function Conf(object) {
if (confirm("Do u want to delete?") == true) {
return true;
}
return false;
}


</script>